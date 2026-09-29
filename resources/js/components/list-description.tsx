/** A list's optional plain-text description, shown directly below the list's h1; renders nothing when absent. */
export default function ListDescription({
    description,
}: {
    description: string | null;
}) {
    return description ? (
        <p className="mt-2 max-w-prose break-words whitespace-pre-line text-muted-foreground">
            {description}
        </p>
    ) : null;
}
