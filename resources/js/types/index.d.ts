export interface User {
    id: number;
    name: string;
    username: string;
    role: 'pembina' | 'siswa' | 'panitia';
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    classGroups: Record<string, string[]>;
};
