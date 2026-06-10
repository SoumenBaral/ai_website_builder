import { computed } from 'vue';

const radiusMap = { none: 'rounded-none', sm: 'rounded-sm', md: 'rounded-md', lg: 'rounded-lg', xl: 'rounded-xl', full: 'rounded-full' };
const shadowMap = { none: '', sm: 'shadow-sm', md: 'shadow-md', lg: 'shadow-lg', xl: 'shadow-xl' };
const borderMap = { none: '', thin: 'border border-gray-200', thick: 'border-2 border-gray-300' };

export function useImageStyle(props) {
    const imageClasses = computed(() => {
        return [
            radiusMap[props.imageRadius] || '',
            shadowMap[props.imageShadow] || '',
            borderMap[props.imageBorder] || '',
        ].filter(Boolean).join(' ');
    });

    return { imageClasses };
}

// For Blade templates - returns inline CSS equivalent
export function imageStyleCSS(radius, shadow, border) {
    const r = { none: '0', sm: '0.125rem', md: '0.375rem', lg: '0.5rem', xl: '0.75rem', full: '9999px' }[radius] || '';
    const s = { none: 'none', sm: '0 1px 2px rgba(0,0,0,0.05)', md: '0 4px 6px rgba(0,0,0,0.1)', lg: '0 10px 15px rgba(0,0,0,0.1)', xl: '0 20px 25px rgba(0,0,0,0.1)' }[shadow] || 'none';
    const b = { none: 'none', thin: '1px solid #e5e7eb', thick: '2px solid #d1d5db' }[border] || 'none';
    return { borderRadius: r, boxShadow: s, border: b };
}
