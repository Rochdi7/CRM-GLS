
import { Link } from "react-router-dom";
import { all_routes } from "../router/all_routes";
import { OverlayTrigger, Tooltip } from "react-bootstrap";

const BlankPage = () => {
  const routes = all_routes;
  return (
    <div className="page-wrapper">
      <div className="content blank-page">
        {/* Page Header */}
        <div className="d-md-flex d-block align-items-center justify-content-between mb-3">
          <div className="my-auto mb-2">
            <h3 className="page-title mb-1">Blank</h3>
            <nav>
              <ol className="breadcrumb mb-0">
                <li className="breadcrumb-item">
                  <Link to={routes.adminDashboard}>Dashboard</Link>
                </li>
                <li className="breadcrumb-item">
                  <Link to="#">Content</Link>
                </li>
                <li className="breadcrumb-item active" aria-current="page">
                  Blank
                </li>
              </ol>
            </nav>
          </div>
          <div className="d-flex my-xl-auto right-content align-items-center flex-wrap">
            <div className="pe-1 mb-2">
              <OverlayTrigger
                placement="top"
                overlay={<Tooltip id="tooltip-top">Refresh</Tooltip>}
              >
                <Link
                  to="#"
                  className="btn btn-outline-light bg-white btn-icon me-1"
                >
                  <i className="ti ti-refresh" />
                </Link>
              </OverlayTrigger>
            </div>
            <div className="pe-1 mb-2">
              <OverlayTrigger
                placement="top"
                overlay={<Tooltip id="tooltip-top">Print</Tooltip>}
              >
                <button
                  type="button"
                  className="btn btn-outline-light bg-white btn-icon me-1"
                >
                  <i className="ti ti-printer" />
                </button>
              </OverlayTrigger>
            </div>
          </div>
        </div>
        {/* /Page Header */}
      </div>
    </div>
  );
};

export default BlankPage;
