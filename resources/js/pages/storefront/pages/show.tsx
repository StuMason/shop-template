import { Seo } from '@/components/seo';

type PageProps = {
    page: {
        slug: string;
        title: string;
        html: string;
    };
};

export default function StaticPage({ page }: PageProps) {
    return (
        <>
            <Seo title={page.title} description={page.title} />

            <article className="mx-auto w-full max-w-3xl px-4 py-12 sm:px-6">
                {/* Owner-authored markdown from resources/markdown, rendered
                    server-side with raw HTML stripped. */}
                <div
                    className="prose prose-neutral dark:prose-invert max-w-none [&_a]:underline [&_blockquote]:my-4 [&_blockquote]:border-l-4 [&_blockquote]:border-amber-400 [&_blockquote]:pl-4 [&_blockquote]:text-muted-foreground [&_h1]:text-3xl [&_h1]:font-semibold [&_h1]:tracking-tight [&_h2]:mt-8 [&_h2]:text-xl [&_h2]:font-semibold [&_li]:my-1 [&_p]:my-3 [&_ul]:list-disc [&_ul]:pl-6"
                    dangerouslySetInnerHTML={{ __html: page.html }}
                />
            </article>
        </>
    );
}
