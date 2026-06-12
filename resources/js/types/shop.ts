export type ShopInfo = {
    name: string;
    tagline: string;
    currency: string;
    contact_email: string;
    address_lookup: boolean;
};

export type ImagePayload = {
    src: string;
    srcset: string;
    alt: string;
};

export type GalleryImage = ImagePayload & {
    id: number;
};

export type ProductCard = {
    id: number;
    name: string;
    slug: string;
    price: string | null;
    compare_at_price: string | null;
    in_stock: boolean;
    image: ImagePayload | null;
};

export type ProductOptionValue = {
    id: number;
    value: string;
};

export type ProductOption = {
    id: number;
    name: string;
    values: ProductOptionValue[];
};

export type ProductVariant = {
    id: number;
    sku: string;
    price: string;
    price_amount: string;
    compare_at_price: string | null;
    in_stock: boolean;
    low_stock: boolean;
    is_default: boolean;
    option_value_ids: number[];
};

export type ProductDetail = {
    id: number;
    name: string;
    slug: string;
    is_digital: boolean;
    description: string | null;
    meta_title: string | null;
    meta_description: string | null;
    images: GalleryImage[];
    options: ProductOption[];
    variants: ProductVariant[];
    categories: { name: string; slug: string }[];
};

export type CategorySummary = {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
};

export type BasketItem = {
    id: number;
    quantity: number;
    line_total: string;
    max_quantity: number;
    variant: {
        id: number;
        sku: string;
        price: string;
        options: string;
    };
    product: {
        name: string;
        slug: string;
        image: ImagePayload | null;
    };
};

export type Basket = {
    items: BasketItem[];
    subtotal: number;
    subtotal_formatted: string;
    discount: number;
    discount_formatted: string | null;
    discount_code: string | null;
    total: number;
    total_formatted: string;
    item_count: number;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
};
