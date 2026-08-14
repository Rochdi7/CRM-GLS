import type { SafeMediaFile } from '@/Types';

interface DocumentLinkProps {
    file: SafeMediaFile;
}

/**
 * Matches the receipt-gallery card in depenses/show.blade.php exactly —
 * images render inline, everything else (PDFs) shows a file-type icon.
 * Uses the real, already-authorized media URL only — never a filesystem
 * path, never the full Spatie Media object.
 */
export default function DocumentLink({ file }: DocumentLinkProps) {
    const isImage = file.mimeType.startsWith('image/');

    return (
        <a
            href={file.url}
            target="_blank"
            rel="noopener noreferrer"
            className="d-block border rounded overflow-hidden text-decoration-none"
        >
            <div
                className="bg-light d-flex align-items-center justify-content-center"
                style={{ height: '140px' }}
            >
                {isImage ? (
                    <img src={file.url} alt={file.name} className="w-100 h-100" style={{ objectFit: 'cover' }} />
                ) : (
                    <i className="fa fa-file-pdf fs-24 text-danger" />
                )}
            </div>
            <div className="p-2">
                <span className="d-block text-truncate fs-13 text-dark">{file.name}</span>
                <span className="text-muted fs-12">{Math.round(file.size / 1024)} KB</span>
            </div>
        </a>
    );
}
