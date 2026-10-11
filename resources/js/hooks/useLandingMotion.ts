import { useEffect, useRef, useState } from 'react';

export default function useLandingMotion() {
    const rootRef = useRef<HTMLDivElement>(null);
    const [loaderPhase, setLoaderPhase] = useState<'loading' | 'leaving' | 'hidden'>('loading');

    useEffect(() => {
        const root = rootRef.current;
        if (!root) return;

        const targets = root.querySelectorAll<HTMLElement>('.el-hero-copy, .el-hero-scene, .el-principles p, .el-section-heading, .el-feature, .el-role, .el-steps > li, .el-faq > div, .el-cta');
        const scene = root.querySelector<HTMLElement>('.el-hero-scene');
        let observer: IntersectionObserver | undefined;
        let frame = 0;
        let exitTimer = 0;
        let safetyTimer = 0;
        let finished = false;

        const finishLoading = () => {
            if (finished) return;
            finished = true;
            window.clearTimeout(safetyTimer);
            setLoaderPhase('leaving');
            exitTimer = window.setTimeout(() => setLoaderPhase('hidden'), 300);
        };

        const updateLayers = () => {
            frame = 0;
            if (!scene) return;
            const bounds = scene.getBoundingClientRect();
            if (bounds.bottom < 0 || bounds.top > window.innerHeight) return;
            const offset = Math.max(-24, Math.min(24, (window.innerHeight / 2 - bounds.top - bounds.height / 2) * .06));
            scene.style.setProperty('--el-layer-back', `${offset}px`);
            scene.style.setProperty('--el-layer-front', `${offset * -1.4}px`);
        };
        const scheduleLayers = () => {
            if (!frame) frame = window.requestAnimationFrame(updateLayers);
        };
        const configureMotion = () => {
            document.documentElement.classList.add('el-smooth-scroll', 'el-motion-full');

            if (!('IntersectionObserver' in window)) {
                targets.forEach(target => target.classList.add('is-visible'));
            } else {
                observer = new IntersectionObserver(entries => {
                    entries.forEach(entry => {
                        if (!entry.isIntersecting) return;
                        entry.target.classList.add('is-visible');
                        observer?.unobserve(entry.target);
                    });
                }, { threshold: 0.08, rootMargin: '0px 0px -24px 0px' });
                targets.forEach(target => {
                    target.classList.add('el-reveal');
                    target.style.setProperty('--el-reveal-delay', `${Math.min(Array.from(target.parentElement?.children ?? []).indexOf(target), 3) * 65}ms`);
                    if (!target.classList.contains('is-visible')) observer?.observe(target);
                });
            }
            scheduleLayers();
        };

        safetyTimer = window.setTimeout(finishLoading, 1500);
        configureMotion();
        window.addEventListener('scroll', scheduleLayers, { passive: true });
        window.addEventListener('resize', scheduleLayers, { passive: true });
        window.addEventListener('load', finishLoading, { once: true });
        if (document.readyState === 'complete') finishLoading();

        return () => {
            observer?.disconnect();
            window.cancelAnimationFrame(frame);
            window.clearTimeout(exitTimer);
            window.clearTimeout(safetyTimer);
            window.removeEventListener('load', finishLoading);
            window.removeEventListener('scroll', scheduleLayers);
            window.removeEventListener('resize', scheduleLayers);
            document.documentElement.classList.remove('el-smooth-scroll');
            document.documentElement.classList.remove('el-motion-full');
        };
    }, []);

    return { rootRef, loaderPhase };
}
