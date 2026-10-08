import MemberForm, { Member } from './MemberForm';
export default function Edit({ student }: { student: Member }) { return <MemberForm student={student} />; }
