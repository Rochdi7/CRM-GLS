import BackofficeLayout from '@/Layouts/BackofficeLayout';

interface PermissionsIndexProps {
    groups: Record<string, Record<string, string>>;
    seededCount: number;
}

export default function PermissionsIndex({ groups, seededCount }: PermissionsIndexProps) {
    return (
        <BackofficeLayout>
            <div className="page-header">
                <h4>Permissions</h4>
            </div>

            <div className="alert alert-info" role="alert">
                Permissions are defined by the application and cannot be created from the
                interface. ({seededCount} seeded)
            </div>

            <div className="row">
                {Object.entries(groups).map(([group, permissions]) => (
                    <div className="col-lg-6" key={group}>
                        <div className="card">
                            <div className="card-header">
                                <h5 className="card-title mb-0">{group}</h5>
                            </div>
                            <div className="card-body p-0">
                                <table className="table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Permission</th>
                                            <th>Machine name</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {Object.entries(permissions).map(([name, label]) => (
                                            <tr key={name}>
                                                <td>{label}</td>
                                                <td>
                                                    <code>{name}</code>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                ))}
            </div>
        </BackofficeLayout>
    );
}
