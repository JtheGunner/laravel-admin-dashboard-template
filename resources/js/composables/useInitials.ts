export type UseInitialsReturn = {
    getInitials: (name?: string) => string;
};

export function getInitials(name?: string): string {
    if (!name) {
        return '';
    }

    const names = name.trim().split(' ');

    if (names.length === 0) {
        return '';
    }

    if (names.length === 1) {
        return names[0].charAt(0).toUpperCase();
    }

    return `${names[0].charAt(0)}${names[names.length - 1].charAt(0)}`.toUpperCase();
}

export function useInitials(): UseInitialsReturn {
    return { getInitials };
}
