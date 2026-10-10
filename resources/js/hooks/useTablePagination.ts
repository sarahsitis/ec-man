import { useState } from 'react';

// Keep the complete attendance draft in its parent form while paging visible rows.
export default function useTablePagination<T>(items: T[], scope = '') {
    const [state, setState] = useState({ perPage: 10, page: 1, scope });
    if (state.scope !== scope) {
        setState({ ...state, page: 1, scope });
    }
    const lastPage = Math.max(1, Math.ceil(items.length / state.perPage));
    const page = state.scope === scope ? Math.min(state.page, lastPage) : 1;
    const start = (page - 1) * state.perPage;
    return {
        items: items.slice(start, start + state.perPage),
        currentPage: page, lastPage, total: items.length, perPage: state.perPage,
        from: items.length ? start + 1 : null, to: items.length ? Math.min(start + state.perPage, items.length) : null,
        onSizeChange: (perPage: number) => setState({ perPage, page: 1, scope }),
        go: (page: number) => setState({ ...state, page, scope }),
    };
}
