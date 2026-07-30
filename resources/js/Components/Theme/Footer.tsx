/** Markup matches components/backoffice/layout/footer.blade.php exactly. */
export default function Footer() {
    return (
        <div className="d-sm-flex align-items-center justify-content-between border-top bg-white p-3 mt-auto">
            <p className="mb-0 text-muted">&copy; {new Date().getFullYear()} GLS CRM</p>
            <p className="mb-0 text-muted">GLS SPRACHENZENTRUM BACKOFFICE</p>
        </div>
    );
}
