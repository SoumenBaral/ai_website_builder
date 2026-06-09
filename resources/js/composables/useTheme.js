const radiusMap = {
    none: '0',
    sm: '0.25rem',
    md: '0.5rem',
    lg: '0.75rem',
    xl: '1rem',
    full: '9999px',
};

export function themeToVars(theme) {
    return {
        '--primary': theme.primaryColor,
        '--secondary': theme.secondaryColor || theme.primaryColor,
        '--bg': theme.backgroundColor,
        '--text': theme.textColor,
        '--font-heading': theme.fontHeading,
        '--font-body': theme.fontBody,
        '--radius': radiusMap[theme.radius] || '0.5rem',
    };
}

export function themeClasses(theme) {
    return theme.mode === 'dark' ? 'dark' : '';
}
