export interface User {
    id: number;
    name: string;
    username: string;
    role: 'pembina' | 'siswa' | 'panitia';
    is_panitia: boolean;
    committee_class_locked: boolean;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    classGroups: Record<string, string[]>;
};
