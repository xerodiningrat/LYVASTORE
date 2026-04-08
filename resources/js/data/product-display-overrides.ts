export type ProductDisplayOverride = {
    name?: string | null;
    categoryTitle?: string | null;
    badge?: string | null;
};

export const applyProductDisplayOverride = <
    T extends {
        id: string;
        name: string;
        categoryTitle?: string | null;
        badge?: string | null;
    },
>(
    product: T,
    override?: ProductDisplayOverride | null,
): T => ({
    ...product,
    name: override?.name || product.name,
    categoryTitle: override?.categoryTitle || product.categoryTitle,
    badge: Object.prototype.hasOwnProperty.call(override ?? {}, 'badge') ? (override?.badge ?? undefined) : product.badge,
});
