export type ProductOrderingOverride = {
    pinned?: boolean;
    sortOrder?: number | null;
};

type ProductIdentity = {
    id: string;
    name: string;
};

export const compareProductsByOrdering = <T extends ProductIdentity>(
    left: T,
    right: T,
    orderingOverrides: Record<string, ProductOrderingOverride | undefined>,
) => {
    const leftOverride = orderingOverrides[left.id];
    const rightOverride = orderingOverrides[right.id];
    const leftPinned = Boolean(leftOverride?.pinned);
    const rightPinned = Boolean(rightOverride?.pinned);

    if (leftPinned !== rightPinned) {
        return leftPinned ? -1 : 1;
    }

    const leftSortOrder = leftOverride?.sortOrder;
    const rightSortOrder = rightOverride?.sortOrder;
    const leftHasSortOrder = typeof leftSortOrder === 'number' && Number.isFinite(leftSortOrder);
    const rightHasSortOrder = typeof rightSortOrder === 'number' && Number.isFinite(rightSortOrder);

    if (leftHasSortOrder && rightHasSortOrder && leftSortOrder !== rightSortOrder) {
        return leftSortOrder - rightSortOrder;
    }

    if (leftHasSortOrder !== rightHasSortOrder) {
        return leftHasSortOrder ? -1 : 1;
    }

    return left.name.localeCompare(right.name, 'id-ID');
};
