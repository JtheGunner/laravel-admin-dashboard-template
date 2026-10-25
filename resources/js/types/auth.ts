export type UserRole = 'admin' | 'editor' | 'viewer';

export type User = {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    /** Stored appearance preference: 'light', 'dark' or 'auto'. */
    dark_mode: string;
    avatar: string | null;
};

export type Auth = {
    user: User;
};

/* @chisel-passkeys */
export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};
/* @end-chisel-passkeys */

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};

export interface PasskeySummary {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string | null;
    last_used_at_diff: string | null;
}
