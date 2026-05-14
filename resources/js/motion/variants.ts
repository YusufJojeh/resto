import type { Transition, Variants } from 'framer-motion';

/**
 * Centralized motion variants. Keep durations short for operations screens
 * (POS/kitchen) — under 200ms for state transitions. Reserve longer motion
 * for landing/auth surfaces only.
 */

export const fastSpring: Transition = {
    type: 'spring',
    stiffness: 380,
    damping: 30,
    mass: 0.8,
};

export const standardEase: Transition = {
    duration: 0.18,
    ease: [0.32, 0.72, 0, 1],
};

export const fadeIn: Variants = {
    hidden: { opacity: 0 },
    visible: { opacity: 1, transition: standardEase },
    exit: { opacity: 0, transition: { duration: 0.12 } },
};

export const slideUp: Variants = {
    hidden: { opacity: 0, y: 8 },
    visible: { opacity: 1, y: 0, transition: standardEase },
    exit: { opacity: 0, y: -4, transition: { duration: 0.12 } },
};

export const cardEntrance: Variants = {
    hidden: { opacity: 0, y: 12 },
    visible: (i: number = 0) => ({
        opacity: 1,
        y: 0,
        transition: { ...standardEase, delay: Math.min(i, 8) * 0.03 },
    }),
};

export const dialog: Variants = {
    hidden: { opacity: 0, scale: 0.96 },
    visible: { opacity: 1, scale: 1, transition: fastSpring },
    exit: { opacity: 0, scale: 0.96, transition: { duration: 0.12 } },
};

export const toast: Variants = {
    hidden: { opacity: 0, y: -8 },
    visible: { opacity: 1, y: 0, transition: fastSpring },
    exit: { opacity: 0, y: -4, transition: { duration: 0.14 } },
};

/** Subtle shimmer used by skeletons. Tied to a CSS keyframe for performance. */
export const skeletonShimmer = 'animate-pulse bg-muted/60';

/**
 * Reduced-motion fallback: returns instant variants. Use with framer-motion's
 * `useReducedMotion()` hook in components.
 */
export const noMotion: Variants = {
    hidden: { opacity: 0 },
    visible: { opacity: 1, transition: { duration: 0 } },
    exit: { opacity: 0, transition: { duration: 0 } },
};
