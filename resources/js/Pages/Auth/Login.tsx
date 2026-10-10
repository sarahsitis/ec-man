import { Brand, LearningArt } from '@/Components/PublicBranding';
import { Icon } from '@/Components/WorkspaceUi';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler, useRef, useState } from 'react';

function PasswordIcon({ visible }: { visible: boolean }) {
    return <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" />{visible && <path d="m3 3 18 18" />}
    </svg>;
}

export default function Login({ status, canResetPassword }: { status?: string; canResetPassword: boolean }) {
    const { data, setData, post, processing, errors, reset } = useForm({ username: '', password: '', remember: false });
    const [showPassword, setShowPassword] = useState(false);
    const requestLock = useRef(false);
    const submit: FormEventHandler = event => {
        event.preventDefault();
        if (processing || requestLock.current) { return; }
        requestLock.current = true;
        post(route('login'), {
            onError: () => requestAnimationFrame(() => document.querySelector<HTMLElement>('[aria-invalid="true"]')?.focus()),
            onFinish: () => { reset('password'); setShowPassword(false); requestLock.current = false; },
        });
    };
    return <div className="ec-landing ec-login" lang="id">
        <Head title="Masuk — EC-Man"><meta name="description" content="Masuk ke EC-Man untuk melanjutkan kegiatan English Club dan memantau perkembangan belajar Anda." /></Head>
        <a href="#login-form" className="el-skip">Lewati ke formulir masuk</a>
        <header className="el-login-header"><Brand /><Link href="/" className="el-login-back"><Icon name="back" />Kembali ke beranda</Link></header>
        <main className="el-login-card">
            <section className="el-login-story" aria-labelledby="login-story-title"><div><p className="el-eyebrow">RUANG TUMBUH ENGLISH CLUB</p><h2 id="login-story-title">Langkah kecil.<br /><em>Potensi besar.</em></h2><p>Kenali kemampuan, temukan minat, dan lanjutkan perjalanan belajar bersama English Club.</p></div><LearningArt /><div className="el-login-story-bottom"><p>Belajar bersama.<br /><strong>Berkembang dengan percaya diri.</strong></p><div className="el-login-roles"><span>Siswa</span><span>Panitia EC</span><span>Pembina</span></div></div></section>
            <section className="el-login-form-panel" aria-labelledby="login-title"><div className="el-login-form-heading"><span className="el-login-welcome-icon"><Icon name="book" /></span><p className="el-eyebrow">SELAMAT DATANG KEMBALI</p><h1 id="login-title">Masuk ke EC-Man</h1><p>Gunakan akun Anda untuk membuka ruang English Club.</p></div>
                {status && <div className="el-login-notice" role="status">{status}</div>}
                <form id="login-form" onSubmit={submit} aria-busy={processing}>
                    <div className="el-login-field"><label htmlFor="username">Username / NIS</label><div className="el-login-input-wrap"><span className="el-login-input-icon"><Icon name="user" /></span><input id="username" name="username" type="text" className="el-login-input" autoFocus autoComplete="username" autoCapitalize="none" spellCheck={false} required disabled={processing} placeholder="Masukkan username atau NIS" value={data.username} onChange={event => setData('username', event.target.value)} aria-invalid={!!errors.username} aria-describedby={`username_hint${errors.username ? ' username_error' : ''}`} /></div><p id="username_hint" className="el-login-hint">Gunakan username atau NIS yang diberikan pembina.</p>{errors.username && <p id="username_error" className="el-login-error" role="alert">{errors.username}</p>}</div>
                    <div className="el-login-field"><label htmlFor="password">Password</label><div className="el-login-input-wrap"><span className="el-login-input-icon"><Icon name="lock" /></span><input id="password" name="password" type={showPassword ? 'text' : 'password'} className="el-login-input el-login-password" autoComplete="current-password" required disabled={processing} placeholder="Masukkan password Anda" value={data.password} onChange={event => setData('password', event.target.value)} aria-invalid={!!errors.password} aria-describedby={errors.password ? 'password_error' : undefined} /><button type="button" className="el-login-password-toggle" disabled={processing} onClick={() => setShowPassword(value => !value)} aria-label={showPassword ? 'Sembunyikan password' : 'Tampilkan password'} aria-pressed={showPassword} aria-controls="password"><PasswordIcon visible={showPassword} /></button></div>{errors.password && <p id="password_error" className="el-login-error" role="alert">{errors.password}</p>}</div>
                    <div className="el-login-options"><label htmlFor="remember"><input id="remember" name="remember" type="checkbox" checked={data.remember} disabled={processing} onChange={event => setData('remember', event.target.checked)} /><span>Ingat saya</span></label>{canResetPassword && <Link href={route('password.request')}>Lupa password?</Link>}</div>
                    <button type="submit" className="el-button el-button-primary el-login-submit" disabled={processing}>{processing ? <><span className="el-login-spinner" aria-hidden="true" />Memproses…</> : <>Masuk<Icon name="arrow" /></>}</button>
                </form>
                <div className="el-login-help"><Icon name="shield" /><p>Belum memiliki akun atau lupa password?<br /><strong>Hubungi pembina English Club Anda.</strong></p></div>
            </section>
        </main>
        <footer className="el-login-footer"><span>English Club · Belajar, berlatih, berkembang.</span><span>© {new Date().getFullYear()} EC-Man</span></footer>
    </div>;
}
