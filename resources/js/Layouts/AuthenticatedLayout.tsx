import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { PropsWithChildren, ReactNode } from 'react';

export default function Authenticated(props: PropsWithChildren<{ header?: ReactNode }>) {
    return <WorkspaceLayout {...props} />;
}
