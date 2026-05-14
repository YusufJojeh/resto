import { useReducedMotion, type Variants } from 'framer-motion';
import { noMotion } from './variants';

/**
 * Returns the original variants, or a no-op replacement if the user prefers
 * reduced motion. Centralizes the reduced-motion check so every consumer
 * doesn't have to repeat it.
 */
export function useMotionVariants(variants: Variants): Variants {
    const shouldReduce = useReducedMotion();
    return shouldReduce ? noMotion : variants;
}
