export const STORAGE_KEY = 'ec-man-demo-v1';
export const ROLES = {
  panitia: {label:'Panitia EC', name:'Raka Pratama', initials:'RP', subtitle:'Asisten penilai pembina'},
  pembina: {label:'Pembina', name:'Bu Sarah', initials:'SS', subtitle:'Penanggung jawab penilaian'},
  siswa: {label:'Siswa', name:'Alya Putri', initials:'AP', subtitle:'Peserta EC · X PPLG RPL 1'}
};
export const CRITERIA = [
  {id:'kehadiran', label:'Kehadiran', weight:20, help:'Konsistensi mengikuti kegiatan dan ketepatan waktu.'},
  {id:'sikap', label:'Sikap & kerja sama', weight:25, help:'Tanggung jawab, disiplin, dan kerja sama dengan anggota.'},
  {id:'partisipasi', label:'Partisipasi', weight:25, help:'Keaktifan, inisiatif, dan kontribusi selama kegiatan.'},
  {id:'keterampilan', label:'Keterampilan', weight:30, help:'Kemampuan menerapkan materi dalam praktik.'}
];
export const STATUS = {
  none:{label:'Belum dinilai',tone:'neutral'}, draft:{label:'Draf',tone:'orange'},
  submitted:{label:'Menunggu pembina',tone:'blue'}, revision:{label:'Perlu revisi',tone:'red'},
  final:{label:'Final',tone:'green'}
};
const names = ['Alya Putri','Bima Saputra','Citra Lestari','Daffa Ramadhan','Nadia Azzahra','Rizky Maulana','Farhan Akbar','Salsa Amalia'];
const sampleScores = {kehadiran:90,sikap:88,partisipasi:85,keterampilan:86};
export function seed(){
  return {version:1,participants:names.map((name,i)=>({id:'p'+(i+1),name,nis:'DEMO-'+String(i+1).padStart(3,'0'),
    className:i%2?'X PPLG RPL 2':'X PPLG RPL 1',group:i<6?'A':'B',
    evaluation:i===1?{status:'submitted',scores:{...sampleScores},note:'Aktif berlatih dan mampu bekerja sama dengan kelompok.',by:'panitia-demo',updatedAt:'2026-10-10T07:30:00.000Z'}:
      i===2?{status:'final',scores:{...sampleScores,partisipasi:92},note:'Inisiatif sangat baik. Pertahankan kedisiplinan selama latihan.',by:'panitia-demo',reviewer:'pembina-demo',updatedAt:'2026-10-10T07:00:00.000Z'}:
      i===3?{status:'draft',scores:{kehadiran:80,sikap:85},note:'Lengkapi hasil observasi praktik.',by:'panitia-demo',updatedAt:'2026-10-10T06:30:00.000Z'}:null})),
    activity:[{text:'Nilai Citra Lestari difinalkan oleh pembina.',time:'2026-10-10T07:00:00.000Z',participantId:'p3'},
      {text:'Penilaian Bima Saputra dikirim ke pembina.',time:'2026-10-10T07:30:00.000Z',participantId:'p2'}]};
}
export function statusOf(p){return p.evaluation?.status || 'none';}
export function allowedPages(role){return role==='siswa'?['dashboard','nilai-saya','panduan']:['dashboard','peserta','penilaian','panduan'];}
export function canView(role,p){return !!p && (role==='pembina'||role==='panitia'&&p.group==='A'||role==='siswa'&&p.id==='p1');}
export function visibleParticipants(state,role){return state.participants.filter(p=>canView(role,p));}
export function canEdit(role,p){return role==='panitia'&&canView(role,p)&&['none','draft','revision'].includes(statusOf(p));}
export function canReview(role,p){return role==='pembina'&&canView(role,p)&&statusOf(p)==='submitted';}
export function scoreValue(scores){
  if(!scores||CRITERIA.some(c=>!Number.isInteger(scores[c.id])||scores[c.id]<0||scores[c.id]>100))return null;
  return Number(CRITERIA.reduce((n,c)=>n+scores[c.id]*c.weight/100,0).toFixed(1));
}
export function grade(value){return value===null?'—':value>=90?'Sangat baik':value>=80?'Baik':value>=70?'Cukup':'Perlu pendampingan';}
export function validateEvaluation(scores,note,submit=false){
  const errors={};
  CRITERIA.forEach(c=>{
    const v=scores[c.id];
    if(v===undefined||v===''||v===null){if(submit)errors[c.id]='Isi nilai untuk '+c.label.toLowerCase()+'.';}
    else if(!Number.isInteger(v)||v<0||v>100)errors[c.id]='Gunakan bilangan bulat antara 0–100.';
  });
  if(typeof note!=='string'||note.length>1000)errors.note='Catatan maksimal 1.000 karakter.';
  else if(submit&&note.trim().length<10)errors.note='Tulis catatan observasi minimal 10 karakter.';
  return errors;
}
export function saveEvaluation(state,role,id,scores,note,submit=false,now=new Date().toISOString()){
  const p=state.participants.find(p=>p.id===id);
  if(!canEdit(role,p))throw new Error('Anda tidak dapat mengubah penilaian peserta ini.');
  const errors=validateEvaluation(scores,note,submit);
  if(Object.keys(errors).length)return {errors};
  const clean=Object.fromEntries(CRITERIA.filter(c=>Number.isInteger(scores[c.id])).map(c=>[c.id,scores[c.id]]));
  p.evaluation={...p.evaluation,status:submit?'submitted':'draft',scores:clean,note:note.trim(),by:'panitia-demo',updatedAt:now};
  state.activity.unshift({text:submit?'Penilaian '+p.name+' dikirim ke pembina.':'Draf nilai '+p.name+' disimpan.',time:now,participantId:id});
  state.activity=state.activity.slice(0,20);
  return {participant:p};
}
export function reviewEvaluation(state,role,id,action,feedback='',now=new Date().toISOString()){
  const p=state.participants.find(p=>p.id===id);
  if(!canReview(role,p))throw new Error('Hanya pembina dapat meninjau penilaian yang sudah dikirim.');
  if(!['final','revision'].includes(action))throw new Error('Tindakan tinjauan tidak valid.');
  if(action==='revision'&&feedback.trim().length<10)return {errors:{feedback:'Tulis arahan revisi minimal 10 karakter.'}};
  if(feedback.length>1000)return {errors:{feedback:'Arahan revisi maksimal 1.000 karakter.'}};
  if(scoreValue(p.evaluation.scores)===null||validateEvaluation(p.evaluation.scores,p.evaluation.note,true).note)throw new Error('Penilaian belum lengkap.');
  p.evaluation={...p.evaluation,status:action,reviewer:'pembina-demo',feedback:feedback.trim(),updatedAt:now};
  state.activity.unshift({text:action==='final'?'Nilai '+p.name+' difinalkan oleh pembina.':'Pembina meminta revisi nilai '+p.name+'.',time:now,participantId:id});
  state.activity=state.activity.slice(0,20);
  return {participant:p};
}
export function studentEvaluation(state){const e=state.participants.find(p=>p.id==='p1')?.evaluation;return e?.status==='final'?e:null;}
export function restore(raw){
  try{
    const data=JSON.parse(raw);const fresh=seed();
    if(data.version!==1||!Array.isArray(data.participants)||!Array.isArray(data.activity))return fresh;
    fresh.participants.forEach(p=>{
      const e=data.participants.find(x=>x.id===p.id)?.evaluation;
      if(e&&['draft','submitted','revision','final'].includes(e.status)&&!Object.keys(validateEvaluation(e.scores||{},e.note||'',e.status!=='draft')).length&&typeof e.updatedAt==='string'&&e.by==='panitia-demo')p.evaluation={status:e.status,scores:e.scores,note:e.note,by:e.by,updatedAt:e.updatedAt,feedback:typeof e.feedback==='string'?e.feedback:'',reviewer:e.reviewer};
      else if(!e)p.evaluation=null;
    });
    fresh.activity=data.activity.filter(a=>typeof a.text==='string'&&typeof a.time==='string'&&fresh.participants.some(p=>p.id===a.participantId)).slice(0,20);
    return fresh;
  }catch{return seed();}
}
