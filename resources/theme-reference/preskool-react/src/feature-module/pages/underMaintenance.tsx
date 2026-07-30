
import { all_routes } from "../router/all_routes";
import { Link } from "react-router-dom";
import ImageWithBasePath from "../../core/common/imageWithBasePath";

const UnderMaintenance = () => {
  const routes = all_routes;
  return (
    <div className="container">
      <div className="row justify-content-center">
        <div className="col-xxl-5 col-xl-5 col-md-6">
          <div className="d-flex flex-column justify-content-between vh-100">
            <div className="text-center p-4">
              <ImageWithBasePath
                src="assets/img/logo.svg"
                alt="img"
                className="img-fluid"
              />
            </div>
            <div className="d-flex flex-column align-items-center justify-content-center mb-4">
              <div className="mb-4">
                <ImageWithBasePath
                  src="assets/img/authentication/under-maintanence.svg"
                  className="img-fluid"
                  alt="Img"
                />
              </div>
              <h3 className="h1 mb-3">We Are Under Maintenance</h3>
              <p className="text-center">
                Please check back later, We are working hard to get <br />
                everything just right.
              </p>
              <Link
                to={routes.adminDashboard}
                className="btn btn-primary d-flex align-items-center"
              >
                <i className="ti ti-arrow-left me-2" />
                Back to Dashboard
              </Link>
            </div>
            <div className="text-center p-3">
              <p>Copyright © 2024 - Preskool</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default UnderMaintenance;
