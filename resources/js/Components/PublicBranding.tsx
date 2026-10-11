import { useTranslation } from '@/i18n/useTranslation';
export function LearningArt() {
    return <svg viewBox="0 0 440 340" fill="none" aria-hidden="true" className="el-learning-art">
        <circle cx="225" cy="168" r="133" fill="#e4d7fb" />
        <circle cx="365" cy="53" r="8" fill="#ec4899" /><circle cx="74" cy="128" r="6" fill="#f9a8d4" />
        <path d="M325 253c-2-58 3-84 22-117 28 18 13 52-11 59 23-14 50-9 46 9-4 20-29 26-49 26" fill="#9a77dd" />
        <path d="M331 274c-2-51 9-101 18-123" stroke="#8b5cf6" strokeWidth="3" strokeLinecap="round" /><path d="M309 269h48l-8 39h-32l-8-39Z" fill="#c58f5f" />
        <path d="M97 231c0-43 16-76 45-76 35 0 54 31 57 70l-102 6Z" fill="#7c3aed" /><path d="M143 181c-31 1-44 31-45 66" stroke="#f9a8d4" strokeWidth="4" strokeLinecap="round" />
        <path d="M123 160v-23h31v26c-8 13-23 13-31-3Z" fill="#cf946b" /><ellipse cx="139" cy="113" rx="30" ry="36" fill="#e1b18b" />
        <path d="M108 118c-9-31 1-59 28-60 25-1 38 16 35 42-16-1-27-10-34-19-3 19-18 28-29 28v9Z" fill="#35234b" /><path d="M134 139c6 3 11 2 15-2" stroke="#8b5f43" strokeWidth="2" strokeLinecap="round" />
        <path d="M232 226c-2-44 14-71 44-71 30 0 48 26 53 77l-97-6Z" fill="#ec4899" /><path d="M264 160v-21h28v24c-8 10-21 10-28-3Z" fill="#ad724b" /><ellipse cx="276" cy="116" rx="29" ry="35" fill="#c58f64" />
        <path d="M247 122c-7-29 1-59 28-59 30 0 43 24 30 51-6-10-8-19-12-25-11 11-29 14-44 9l-2 24Z" fill="#35234b" /><path d="M267 139c5 3 10 3 15-1" stroke="#79523b" strokeWidth="2" strokeLinecap="round" />
        <path d="M270 184c-1 19-10 38-37 43" stroke="#c58f64" strokeWidth="15" strokeLinecap="round" /><path d="M158 183c4 26 11 43 39 50" stroke="#e1b18b" strokeWidth="15" strokeLinecap="round" />
        <path d="M86 249h252" stroke="#8b5cf6" strokeWidth="7" strokeLinecap="round" /><path d="M110 252v56m201-56v56" stroke="#8b5cf6" strokeWidth="6" strokeLinecap="round" />
        <path d="M166 214c15-5 32-4 47 6 15-10 32-11 47-6v31c-15-5-32-4-47 3-15-7-32-8-47-3v-31Z" fill="#fffdf6" stroke="#8b7af3" strokeWidth="2" /><path d="M213 221v24m-35-19 23 3m-23 6 23 3m24-9 23-3m-23 12 23-3" stroke="#b5a1ed" strokeWidth="2" strokeLinecap="round" />
        <rect x="183" y="51" width="58" height="38" rx="12" fill="#fffdf6" /><path d="m195 87-2 10 14-9" fill="#fffdf6" /><path d="m197 69 6 6 13-13" stroke="#7c3aed" strokeWidth="3" strokeLinecap="round" />
        <path d="M79 58h24m-12-12v24M360 108h16m-8-8v16" stroke="#f9a8d4" strokeWidth="2" strokeLinecap="round" /><path d="M77 310h291" stroke="#d4c5f1" strokeWidth="2" strokeLinecap="round" />
    </svg>;
}

export function Brand() {
    const { t, locale, dateLocale } = useTranslation();
    return <a className="el-brand" href="/" aria-label={t("EC-Man beranda")}><span className="el-brand-mark">ec</span><span><strong>EC-Man<span>.</span></strong><small>ENGLISH CLUB MANAGER</small></span></a>;
}
