
import { Link } from "react-router-dom";
import Select from "react-select";
import Slider from "react-slick";
import "slick-carousel/slick/slick.css";
import "slick-carousel/slick/slick-theme.css";
import ImageWithBasePath from "../../core/common/imageWithBasePath";

const NotesContent = () => {
  const optionsBulk = [
    { value: "bulkActions", label: "Bulk Actions" },
    { value: "deleteMarked", label: "Delete Marked" },
    { value: "unmarkAll", label: "Unmark All" },
    { value: "markAll", label: "Mark All" },
  ];

  const optionsRecent = [
    { value: "recent", label: "Recent" },
    { value: "lastModified", label: "Last Modified" },
    { value: "lastModifiedByMe", label: "Last Modified by me" },
  ];

  const settings = {
    dots: false,
    autoplay: false,
    slidesToShow: 3,
    margin: 24,
    speed: 500,
    responsive: [
      {
        breakpoint: 992,
        settings: {
          slidesToShow: 2,
        },
      },
      {
        breakpoint: 800,
        settings: {
          slidesToShow: 2,
        },
      },
      {
        breakpoint: 776,
        settings: {
          slidesToShow: 2,
        },
      },
      {
        breakpoint: 567,
        settings: {
          slidesToShow: 2,
        },
      },
    ],
  };
  return (
    <>
      <div className="bg-white rounded-3 d-flex align-items-center justify-content-between flex-wrap my-4 p-3 pb-0">
          <div className="d-flex align-items-center mb-3">
            <div className="me-3">
            <Select classNamePrefix="react-select"
              className="select"
              options={optionsBulk}
              defaultValue={optionsBulk[0]}
            />
            </div>
            <Link to="#" className="btn btn-light">
              Apply
            </Link>
          </div>
          <div className="form-sort mb-3">
            <i className="ti ti-filter feather-filter info-img ms-1" />
            <Select classNamePrefix="react-select"
            className="select"
            options={optionsRecent}
            defaultValue={optionsRecent[0]}
          />
          </div>
        </div>
        <div className="tab-content" id="v-pills-tabContent">
          <div
            className="tab-pane fade active show"
            id="v-pills-profile"
            role="tabpanel"
            aria-labelledby="v-pills-profile-tab"
          >
            <div className="border-bottom mb-4 pb-4">
              <div className="row">
                <div className="col-md-12">
                  <div className="d-flex align-items-center justify-content-between flex-wrap mb-2">
                    <div className="d-flex align-items-center mb-3">
                      <h4>Important Notes </h4>
                      <div className="owl-nav slide-nav5 text-end nav-control ms-3" />
                    </div>
                    <div className="notes-close mb-3">
                      <Link
                        to="#"
                        className="text-danger fs-15"
                      >
                        <i className="fas fa-times me-1" /> Close{" "}
                      </Link>
                    </div>
                  </div>
                </div>
                <div className="col-md-12">
                  <Slider {...settings} className="notes-slider owl-carousel">
                    <div className="card rounded-3 mb-0">
                      <div className="card-body p-4">
                        <div className="d-flex align-items-center justify-content-between">
                          <span className="badge bg-outline-warning d-inline-flex align-items-center">
                            <i className="fas fa-circle fs-6 me-1" />
                            Medium
                          </span>
                          <div>
                            <Link
                              to="#"
                              data-bs-toggle="dropdown"
                              aria-expanded="false"
                            >
                              <i className="fas fa-ellipsis-v" />
                            </Link>
                            <div className="dropdown-menu notes-menu dropdown-menu-end">
                              <Link
                                to="#"
                                className="dropdown-item"
                                data-bs-toggle="modal"
                                data-bs-target="#edit-note-units"
                              >
                                <span>
                                  <i data-feather="edit" />
                                </span>
                                Edit
                              </Link>
                              <Link
                                to="#"
                                className="dropdown-item"
                                data-bs-toggle="modal"
                                data-bs-target="#delete-modal"
                              >
                                <span>
                                  <i data-feather="trash-2" />
                                </span>
                                Delete
                              </Link>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                <span>
                                  <i data-feather="star" />
                                </span>
                                Not Important
                              </Link>
                              <Link
                                to="#"
                                className="dropdown-item"
                                data-bs-toggle="modal"
                                data-bs-target="#view-note-units"
                              >
                                <span>
                                  <i data-feather="eye" />
                                </span>
                                View
                              </Link>
                            </div>
                          </div>
                        </div>
                        <div className="my-3">
                          <h5 className="text-truncate mb-1">
                            <Link to="#">
                              Plan a trip to another country
                            </Link>
                          </h5>
                          <p className="mb-3 d-flex align-items-center text-dark">
                            <i className="ti ti-calendar me-1" />
                            20 Jan 2024
                          </p>
                          <p className="text-truncate line-clamb-2 text-wrap">
                            Space, the final frontier. These are the voyages of
                            the Starship Enterprise.
                          </p>
                        </div>
                        <div className="d-flex align-items-center justify-content-between border-top pt-3">
                          <div className="d-flex align-items-center">
                            <Link
                              to="#"
                              className="avatar avatar-md me-2"
                            >
                              <ImageWithBasePath
                                src="./assets/img/profiles/avatar-01.jpg"
                                alt="Profile"
                                className="img-fluid rounded-circle"
                              />
                            </Link>
                            <span className="text-info d-flex align-items-center">
                              <i className="fas fa-square square-rotate fs-10 me-1" />
                              Personal
                            </span>
                          </div>
                          <div className="d-flex align-items-center">
                            <Link to="#" className="me-2">
                              <span>
                                <i className="fas fa-star text-warning" />
                              </span>
                            </Link>
                            <Link to="#">
                              <span>
                                <i className="ti ti-trash text-danger" />
                              </span>
                            </Link>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="card rounded-3 mb-0">
                      <div className="card-body p-4">
                        <div className="d-flex align-items-center justify-content-between">
                          <span className="badge bg-outline-danger d-inline-flex align-items-center">
                            <i className="fas fa-circle fs-6 me-1" />
                            Low
                          </span>
                          <div>
                            <Link
                              to="#"
                              data-bs-toggle="dropdown"
                              aria-expanded="false"
                            >
                              <i className="fas fa-ellipsis-v" />
                            </Link>
                            <div className="dropdown-menu notes-menu dropdown-menu-end">
                              <Link
                                to="#"
                                className="dropdown-item"
                                data-bs-toggle="modal"
                                data-bs-target="#edit-note-units"
                              >
                                <span>
                                  <i data-feather="edit" />
                                </span>
                                Edit
                              </Link>
                              <Link
                                to="#"
                                className="dropdown-item"
                                data-bs-toggle="modal"
                                data-bs-target="#delete-modal"
                              >
                                <span>
                                  <i data-feather="trash-2" />
                                </span>
                                Delete
                              </Link>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                <span>
                                  <i data-feather="star" />
                                </span>
                                Not Important
                              </Link>
                              <Link
                                to="#"
                                className="dropdown-item"
                                data-bs-toggle="modal"
                                data-bs-target="#view-note-units"
                              >
                                <span>
                                  <i data-feather="eye" />
                                </span>
                                View
                              </Link>
                            </div>
                          </div>
                        </div>
                        <div className="my-3">
                          <h5 className="text-truncate mb-1">
                            <Link to="#">
                              Improve touch typing
                            </Link>
                          </h5>
                          <p className="mb-3 d-flex align-items-center text-dark">
                            <i className="ti ti-calendar me-1" />
                            22 Jan 2024
                          </p>
                          <p className="text-truncate line-clamb-2 text-wrap">
                            Well, the way they make shows is, they make one
                            show.
                          </p>
                        </div>
                        <div className="d-flex align-items-center justify-content-between border-top pt-3">
                          <div className="d-flex align-items-center">
                            <Link
                              to="#"
                              className="avatar avatar-md me-2"
                            >
                              <ImageWithBasePath
                                src="./assets/img/profiles/avatar-02.jpg"
                                alt="Profile"
                                className="img-fluid rounded-circle"
                              />
                            </Link>
                            <span className="text-success d-flex align-items-center">
                              <i className="fas fa-square square-rotate fs-10 me-1" />
                              Work
                            </span>
                          </div>
                          <div className="d-flex align-items-center">
                            <Link to="#" className="me-2">
                              <span>
                                <i className="fas fa-star text-warning" />
                              </span>
                            </Link>
                            <Link to="#">
                              <span>
                                <i className="ti ti-trash text-danger" />
                              </span>
                            </Link>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="card rounded-3 mb-0">
                      <div className="card-body p-4">
                        <div className="d-flex align-items-center justify-content-between">
                          <span className="badge bg-outline-danger d-inline-flex align-items-center">
                            <i className="fas fa-circle fs-6 me-1" />
                            Low
                          </span>
                          <div>
                            <Link
                              to="#"
                              data-bs-toggle="dropdown"
                              aria-expanded="false"
                            >
                              <i className="fas fa-ellipsis-v" />
                            </Link>
                            <div className="dropdown-menu notes-menu dropdown-menu-end">
                              <Link
                                to="#"
                                className="dropdown-item"
                                data-bs-toggle="modal"
                                data-bs-target="#edit-note-units"
                              >
                                <span>
                                  <i data-feather="edit" />
                                </span>
                                Edit
                              </Link>
                              <Link
                                to="#"
                                className="dropdown-item"
                                data-bs-toggle="modal"
                                data-bs-target="#delete-modal"
                              >
                                <span>
                                  <i data-feather="trash-2" />
                                </span>
                                Delete
                              </Link>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                <span>
                                  <i data-feather="star" />
                                </span>
                                Not Important
                              </Link>
                              <Link
                                to="#"
                                className="dropdown-item"
                                data-bs-toggle="modal"
                                data-bs-target="#view-note-units"
                              >
                                <span>
                                  <i data-feather="eye" />
                                </span>
                                View
                              </Link>
                            </div>
                          </div>
                        </div>
                        <div className="my-3">
                          <h5 className="text-truncate mb-1">
                            <Link to="#">Learn calligraphy</Link>
                          </h5>
                          <p className="mb-3 d-flex align-items-center text-dark">
                            <i className="ti ti-calendar me-1" />
                            24 Jan 2024
                          </p>
                          <p className="text-truncate line-clamb-2 text-wrap">
                            Calligraphy, the art of beautiful handwriting. The
                            term may derive from the Greek words.{" "}
                          </p>
                        </div>
                        <div className="d-flex align-items-center justify-content-between border-top pt-3">
                          <div className="d-flex align-items-center">
                            <Link
                              to="#"
                              className="avatar avatar-md me-2"
                            >
                              <ImageWithBasePath
                                src="./assets/img/profiles/avatar-03.jpg"
                                alt="Profile"
                                className="img-fluid rounded-circle"
                              />
                            </Link>
                            <span className="text-info d-flex align-items-center">
                              <i className="fas fa-square square-rotate fs-10 me-1" />
                              Social
                            </span>
                          </div>
                          <div className="d-flex align-items-center">
                            <Link to="#" className="me-2">
                              <span>
                                <i className="fas fa-star text-warning" />
                              </span>
                            </Link>
                            <Link to="#">
                              <span>
                                <i className="ti ti-trash text-danger" />
                              </span>
                            </Link>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="card rounded-3 mb-0">
                      <div className="card-body p-4">
                        <div className="d-flex align-items-center justify-content-between">
                          <span className="badge bg-outline-warning d-inline-flex align-items-center">
                            <i className="fas fa-circle fs-6 me-1" />
                            Medium
                          </span>
                          <div>
                            <Link
                              to="#"
                              data-bs-toggle="dropdown"
                              aria-expanded="false"
                            >
                              <i className="fas fa-ellipsis-v" />
                            </Link>
                            <div className="dropdown-menu notes-menu dropdown-menu-end">
                              <Link
                                to="#"
                                className="dropdown-item"
                                data-bs-toggle="modal"
                                data-bs-target="#edit-note-units"
                              >
                                <span>
                                  <i data-feather="edit" />
                                </span>
                                Edit
                              </Link>
                              <Link
                                to="#"
                                className="dropdown-item"
                                data-bs-toggle="modal"
                                data-bs-target="#delete-modal"
                              >
                                <span>
                                  <i data-feather="trash-2" />
                                </span>
                                Delete
                              </Link>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                <span>
                                  <i data-feather="star" />
                                </span>
                                Not Important
                              </Link>
                              <Link
                                to="#"
                                className="dropdown-item"
                                data-bs-toggle="modal"
                                data-bs-target="#view-note-units"
                              >
                                <span>
                                  <i data-feather="eye" />
                                </span>
                                View
                              </Link>
                            </div>
                          </div>
                        </div>
                        <div className="my-3">
                          <h5 className="text-truncate mb-1">
                            <Link to="#">
                              Plan a trip to another country
                            </Link>
                          </h5>
                          <p className="mb-3 d-flex align-items-center text-dark">
                            <i className="ti ti-calendar me-1" />
                            25 Jan 2024
                          </p>
                          <p className="text-truncate line-clamb-2 text-wrap">
                            Space, the final frontier. These are the voyages of
                            the Starship Enterprise.
                          </p>
                        </div>
                        <div className="d-flex align-items-center justify-content-between border-top pt-3">
                          <div className="d-flex align-items-center">
                            <Link
                              to="#"
                              className="avatar avatar-md me-2"
                            >
                              <ImageWithBasePath
                                src="./assets/img/profiles/avatar-01.jpg"
                                alt="Profile"
                                className="img-fluid rounded-circle"
                              />
                            </Link>
                            <span className="text-info d-flex align-items-center">
                              <i className="fas fa-square square-rotate fs-10 me-1" />
                              Personal
                            </span>
                          </div>
                          <div className="d-flex align-items-center">
                            <Link to="#" className="me-2">
                              <span>
                                <i className="fas fa-star text-warning" />
                              </span>
                            </Link>
                            <Link to="#">
                              <span>
                                <i className="ti ti-trash text-danger" />
                              </span>
                            </Link>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="card rounded-3 mb-0">
                      <div className="card-body p-4">
                        <div className="d-flex align-items-center justify-content-between">
                          <span className="badge bg-outline-danger d-inline-flex align-items-center">
                            <i className="fas fa-circle fs-6 me-1" />
                            Low
                          </span>
                          <div>
                            <Link
                              to="#"
                              data-bs-toggle="dropdown"
                              aria-expanded="false"
                            >
                              <i className="fas fa-ellipsis-v" />
                            </Link>
                            <div className="dropdown-menu notes-menu dropdown-menu-end">
                              <Link
                                to="#"
                                className="dropdown-item"
                                data-bs-toggle="modal"
                                data-bs-target="#edit-note-units"
                              >
                                <span>
                                  <i data-feather="edit" />
                                </span>
                                Edit
                              </Link>
                              <Link
                                to="#"
                                className="dropdown-item"
                                data-bs-toggle="modal"
                                data-bs-target="#delete-modal"
                              >
                                <span>
                                  <i data-feather="trash-2" />
                                </span>
                                Delete
                              </Link>
                              <Link
                                to="#"
                                className="dropdown-item"
                              >
                                <span>
                                  <i data-feather="star" />
                                </span>
                                Not Important
                              </Link>
                              <Link
                                to="#"
                                className="dropdown-item"
                                data-bs-toggle="modal"
                                data-bs-target="#view-note-units"
                              >
                                <span>
                                  <i data-feather="eye" />
                                </span>
                                View
                              </Link>
                            </div>
                          </div>
                        </div>
                        <div className="my-3">
                          <h5 className="text-truncate mb-1">
                            <Link to="#">
                              Improve touch typing
                            </Link>
                          </h5>
                          <p className="mb-3 d-flex align-items-center text-dark">
                            <i className="ti ti-calendar me-1" />
                            26 Jan 2024
                          </p>
                          <p className="text-truncate line-clamb-2 text-wrap">
                            Well, the way they make shows is, they make one
                            show.
                          </p>
                        </div>
                        <div className="d-flex align-items-center justify-content-between border-top pt-3">
                          <div className="d-flex align-items-center">
                            <Link
                              to="#"
                              className="avatar avatar-md me-2"
                            >
                              <ImageWithBasePath
                                src="./assets/img/profiles/avatar-02.jpg"
                                alt="Profile"
                                className="img-fluid rounded-circle"
                              />
                            </Link>
                            <span className="text-success d-flex align-items-center">
                              <i className="fas fa-square square-rotate fs-10 me-1" />
                              Work
                            </span>
                          </div>
                          <div className="d-flex align-items-center">
                            <Link to="#" className="me-2">
                              <span>
                                <i className="fas fa-star text-warning" />
                              </span>
                            </Link>
                            <Link to="#">
                              <span>
                                <i className="ti ti-trash text-danger" />
                              </span>
                            </Link>
                          </div>
                        </div>
                      </div>
                    </div>
                  </Slider>
                </div>
              </div>
            </div>
            <div className="row">
              <div className="col-md-4 d-flex">
                <div className="card rounded-3 mb-4 flex-fill">
                  <div className="card-body p-4">
                    <div className="d-flex align-items-center justify-content-between">
                      <span className="badge bg-outline-success d-inline-flex align-items-center">
                        <i className="fas fa-circle fs-6 me-1" />
                        High
                      </span>
                      <div>
                        <Link
                          to="#"
                          data-bs-toggle="dropdown"
                          aria-expanded="false"
                        >
                          <i className="fas fa-ellipsis-v" />
                        </Link>
                        <div className="dropdown-menu notes-menu dropdown-menu-end">
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#edit-note-units"
                          >
                            <span>
                              <i data-feather="edit" />
                            </span>
                            Edit
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#delete-modal"
                          >
                            <span>
                              <i data-feather="trash-2" />
                            </span>
                            Delete
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                          >
                            <span>
                              <i data-feather="star" />
                            </span>
                            Not Important
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#view-note-units"
                          >
                            <span>
                              <i data-feather="eye" />
                            </span>
                            View
                          </Link>
                        </div>
                      </div>
                    </div>
                    <div className="my-3">
                      <h5 className="text-truncate mb-1">
                        <Link to="#">Backup Files EOD</Link>
                      </h5>
                      <p className="mb-3 d-flex align-items-center text-dark">
                        <i className="ti ti-calendar me-1" />
                        20 Jan 2024
                      </p>
                      <p className="text-truncate line-clamb-2 text-wrap">
                        Project files should be took backup before end of the
                        day.
                      </p>
                    </div>
                    <div className="d-flex align-items-center justify-content-between border-top pt-3">
                      <div className="d-flex align-items-center">
                        <Link
                          to="#"
                          className="avatar avatar-md me-2"
                        >
                          <ImageWithBasePath
                            src="./assets/img/profiles/avatar-05.jpg"
                            alt="Profile"
                            className="img-fluid rounded-circle"
                          />
                        </Link>
                        <span className="text-info d-flex align-items-center">
                          <i className="fas fa-square square-rotate fs-10 me-1" />
                          Personal
                        </span>
                      </div>
                      <div className="d-flex align-items-center">
                        <Link to="#" className="me-2">
                          <span>
                            <i className="fas fa-star text-warning" />
                          </span>
                        </Link>
                        <Link to="#">
                          <span>
                            <i className="ti ti-trash text-danger" />
                          </span>
                        </Link>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-4 d-flex">
                <div className="card rounded-3 mb-4 flex-fill">
                  <div className="card-body p-4">
                    <div className="d-flex align-items-center justify-content-between">
                      <span className="badge bg-outline-danger d-inline-flex align-items-center">
                        <i className="fas fa-circle fs-6 me-1" />
                        Low
                      </span>
                      <div>
                        <Link
                          to="#"
                          data-bs-toggle="dropdown"
                          aria-expanded="false"
                        >
                          <i className="fas fa-ellipsis-v" />
                        </Link>
                        <div className="dropdown-menu notes-menu dropdown-menu-end">
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#edit-note-units"
                          >
                            <span>
                              <i data-feather="edit" />
                            </span>
                            Edit
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#delete-modal"
                          >
                            <span>
                              <i data-feather="trash-2" />
                            </span>
                            Delete
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                          >
                            <span>
                              <i data-feather="star" />
                            </span>
                            Not Important
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#view-note-units"
                          >
                            <span>
                              <i data-feather="eye" />
                            </span>
                            View
                          </Link>
                        </div>
                      </div>
                    </div>
                    <div className="my-3">
                      <h5 className="text-truncate mb-1">
                        <Link to="#">Download Server Logs</Link>
                      </h5>
                      <p className="mb-3 d-flex align-items-center text-dark">
                        <i className="ti ti-calendar me-1" />
                        25 Jan 2024
                      </p>
                      <p className="text-truncate line-clamb-2 text-wrap">
                        Server log is a text document that contains a record of
                        all activity.
                      </p>
                    </div>
                    <div className="d-flex align-items-center justify-content-between border-top pt-3">
                      <div className="d-flex align-items-center">
                        <Link
                          to="#"
                          className="avatar avatar-md me-2"
                        >
                          <ImageWithBasePath
                            src="./assets/img/profiles/avatar-06.jpg"
                            alt="Profile"
                            className="img-fluid rounded-circle"
                          />
                        </Link>
                        <span className="text-success d-flex align-items-center">
                          <i className="fas fa-square square-rotate fs-10 me-1" />
                          Work
                        </span>
                      </div>
                      <div className="d-flex align-items-center">
                        <Link to="#" className="me-2">
                          <span>
                            <i className="fas fa-star text-warning" />
                          </span>
                        </Link>
                        <Link to="#">
                          <span>
                            <i className="ti ti-trash text-danger" />
                          </span>
                        </Link>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-4 d-flex">
                <div className="card rounded-3 mb-4 flex-fill">
                  <div className="card-body p-4">
                    <div className="d-flex align-items-center justify-content-between">
                      <span className="badge bg-outline-warning d-inline-flex align-items-center">
                        <i className="fas fa-circle fs-6 me-1" />
                        Medium
                      </span>
                      <div>
                        <Link
                          to="#"
                          data-bs-toggle="dropdown"
                          aria-expanded="false"
                        >
                          <i className="fas fa-ellipsis-v" />
                        </Link>
                        <div className="dropdown-menu notes-menu dropdown-menu-end">
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#edit-note-units"
                          >
                            <span>
                              <i data-feather="edit" />
                            </span>
                            Edit
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#delete-modal"
                          >
                            <span>
                              <i data-feather="trash-2" />
                            </span>
                            Delete
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                          >
                            <span>
                              <i data-feather="star" />
                            </span>
                            Not Important
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#view-note-units"
                          >
                            <span>
                              <i data-feather="eye" />
                            </span>
                            View
                          </Link>
                        </div>
                      </div>
                    </div>
                    <div className="my-3">
                      <h5 className="text-truncate mb-1">
                        <Link to="#">Team meet at Starbucks</Link>
                      </h5>
                      <p className="mb-3 d-flex align-items-center text-dark">
                        <i className="ti ti-calendar me-1" />
                        26 Jan 2024
                      </p>
                      <p className="text-truncate line-clamb-2 text-wrap">
                        Meeting all teamets at Starbucks for identifying them
                        all.
                      </p>
                    </div>
                    <div className="d-flex align-items-center justify-content-between border-top pt-3">
                      <div className="d-flex align-items-center">
                        <Link
                          to="#"
                          className="avatar avatar-md me-2"
                        >
                          <ImageWithBasePath
                            src="./assets/img/profiles/avatar-07.jpg"
                            alt="Profile"
                            className="img-fluid rounded-circle"
                          />
                        </Link>
                        <span className="text-warning d-flex align-items-center">
                          <i className="fas fa-square square-rotate fs-10 me-1" />
                          Social
                        </span>
                      </div>
                      <div className="d-flex align-items-center">
                        <Link to="#" className="me-2">
                          <span>
                            <i className="fas fa-star text-warning" />
                          </span>
                        </Link>
                        <Link to="#">
                          <span>
                            <i className="ti ti-trash text-danger" />
                          </span>
                        </Link>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-4 d-flex">
                <div className="card rounded-3 mb-4 flex-fill">
                  <div className="card-body p-4">
                    <div className="d-flex align-items-center justify-content-between">
                      <span className="badge bg-outline-success d-inline-flex align-items-center">
                        <i className="fas fa-circle fs-6 me-1" />
                        High
                      </span>
                      <div>
                        <Link
                          to="#"
                          data-bs-toggle="dropdown"
                          aria-expanded="false"
                        >
                          <i className="fas fa-ellipsis-v" />
                        </Link>
                        <div className="dropdown-menu notes-menu dropdown-menu-end">
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#edit-note-units"
                          >
                            <span>
                              <i data-feather="edit" />
                            </span>
                            Edit
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#delete-modal"
                          >
                            <span>
                              <i data-feather="trash-2" />
                            </span>
                            Delete
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                          >
                            <span>
                              <i data-feather="star" />
                            </span>
                            Not Important
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#view-note-units"
                          >
                            <span>
                              <i data-feather="eye" />
                            </span>
                            View
                          </Link>
                        </div>
                      </div>
                    </div>
                    <div className="my-3">
                      <h5 className="text-truncate mb-1">
                        <Link to="#">Create a compost pile</Link>
                      </h5>
                      <p className="mb-3 d-flex align-items-center text-dark">
                        <i className="ti ti-calendar me-1" />
                        27 Jan 2024
                      </p>
                      <p className="text-truncate line-clamb-2 text-wrap">
                        Compost pile refers to fruit and vegetable scraps, used
                        tea, coffee grounds etc..
                      </p>
                    </div>
                    <div className="d-flex align-items-center justify-content-between border-top pt-3">
                      <div className="d-flex align-items-center">
                        <Link
                          to="#"
                          className="avatar avatar-md me-2"
                        >
                          <ImageWithBasePath
                            src="./assets/img/profiles/avatar-08.jpg"
                            alt="Profile"
                            className="img-fluid rounded-circle"
                          />
                        </Link>
                        <span className="text-warning d-flex align-items-center">
                          <i className="fas fa-square square-rotate fs-10 me-1" />
                          Social
                        </span>
                      </div>
                      <div className="d-flex align-items-center">
                        <Link to="#" className="me-2">
                          <span>
                            <i className="fas fa-star text-warning" />
                          </span>
                        </Link>
                        <Link to="#">
                          <span>
                            <i className="ti ti-trash text-danger" />
                          </span>
                        </Link>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-4 d-flex">
                <div className="card rounded-3 mb-4 flex-fill">
                  <div className="card-body p-4">
                    <div className="d-flex align-items-center justify-content-between">
                      <span className="badge bg-outline-danger d-inline-flex align-items-center">
                        <i className="fas fa-circle fs-6 me-1" />
                        Low
                      </span>
                      <div>
                        <Link
                          to="#"
                          data-bs-toggle="dropdown"
                          aria-expanded="false"
                        >
                          <i className="fas fa-ellipsis-v" />
                        </Link>
                        <div className="dropdown-menu notes-menu dropdown-menu-end">
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#edit-note-units"
                          >
                            <span>
                              <i data-feather="edit" />
                            </span>
                            Edit
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#delete-modal"
                          >
                            <span>
                              <i data-feather="trash-2" />
                            </span>
                            Delete
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                          >
                            <span>
                              <i data-feather="star" />
                            </span>
                            Not Important
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#view-note-units"
                          >
                            <span>
                              <i data-feather="eye" />
                            </span>
                            View
                          </Link>
                        </div>
                      </div>
                    </div>
                    <div className="my-3">
                      <h5 className="text-truncate mb-1">
                        <Link to="#">
                          Take a hike at a local park
                        </Link>
                      </h5>
                      <p className="mb-3 d-flex align-items-center text-dark">
                        <i className="ti ti-calendar me-1" />
                        28 Jan 2024
                      </p>
                      <p className="text-truncate line-clamb-2 text-wrap">
                        Hiking involves a long energetic walk in a natural
                        environment.
                      </p>
                    </div>
                    <div className="d-flex align-items-center justify-content-between border-top pt-3">
                      <div className="d-flex align-items-center">
                        <Link
                          to="#"
                          className="avatar avatar-md me-2"
                        >
                          <ImageWithBasePath
                            src="./assets/img/profiles/avatar-09.jpg"
                            alt="Profile"
                            className="img-fluid rounded-circle"
                          />
                        </Link>
                        <span className="text-info d-flex align-items-center">
                          <i className="fas fa-square square-rotate fs-10 me-1" />
                          Personal
                        </span>
                      </div>
                      <div className="d-flex align-items-center">
                        <Link to="#" className="me-2">
                          <span>
                            <i className="fas fa-star text-warning" />
                          </span>
                        </Link>
                        <Link to="#">
                          <span>
                            <i className="ti ti-trash text-danger" />
                          </span>
                        </Link>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-4 d-flex">
                <div className="card rounded-3 mb-4 flex-fill">
                  <div className="card-body p-4">
                    <div className="d-flex align-items-center justify-content-between">
                      <span className="badge bg-outline-info d-inline-flex align-items-center">
                        <i className="fas fa-circle fs-6 me-1" />
                        medium
                      </span>
                      <div>
                        <Link
                          to="#"
                          data-bs-toggle="dropdown"
                          aria-expanded="false"
                        >
                          <i className="fas fa-ellipsis-v" />
                        </Link>
                        <div className="dropdown-menu notes-menu dropdown-menu-end">
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#edit-note-units"
                          >
                            <span>
                              <i data-feather="edit" />
                            </span>
                            Edit
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#delete-modal"
                          >
                            <span>
                              <i data-feather="trash-2" />
                            </span>
                            Delete
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                          >
                            <span>
                              <i data-feather="star" />
                            </span>
                            Not Important
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#view-note-units"
                          >
                            <span>
                              <i data-feather="eye" />
                            </span>
                            View
                          </Link>
                        </div>
                      </div>
                    </div>
                    <div className="my-3">
                      <h5 className="text-truncate mb-1">
                        <Link to="#">
                          Research a topic interested
                        </Link>
                      </h5>
                      <p className="mb-3 d-flex align-items-center text-dark">
                        <i className="ti ti-calendar me-1" />
                        28 Jan 2024
                      </p>
                      <p className="text-truncate line-clamb-2 text-wrap">
                        Research a topic interested by listen actively and
                        attentively.
                      </p>
                    </div>
                    <div className="d-flex align-items-center justify-content-between border-top pt-3">
                      <div className="d-flex align-items-center">
                        <Link
                          to="#"
                          className="avatar avatar-md me-2"
                        >
                          <ImageWithBasePath
                            src="./assets/img/profiles/avatar-10.jpg"
                            alt="Profile"
                            className="img-fluid rounded-circle"
                          />
                        </Link>
                        <span className="text-success d-flex align-items-center">
                          <i className="fas fa-square square-rotate fs-10 me-1" />
                          Work
                        </span>
                      </div>
                      <div className="d-flex align-items-center">
                        <Link to="#" className="me-2">
                          <span>
                            <i className="fas fa-star text-warning" />
                          </span>
                        </Link>
                        <Link to="#">
                          <span>
                            <i className="ti ti-trash text-danger" />
                          </span>
                        </Link>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div
            className="tab-pane fade"
            id="v-pills-messages"
            role="tabpanel"
            aria-labelledby="v-pills-messages-tab"
          >
            <div className="row">
              <div className="col-md-4 d-flex">
                <div className="card rounded-3 mb-4 flex-fill">
                  <div className="card-body p-4">
                    <div className="d-flex align-items-center justify-content-between">
                      <span className="badge bg-outline-success d-inline-flex align-items-center">
                        <i className="fas fa-circle fs-6 me-1" />
                        High
                      </span>
                      <div>
                        <Link
                          to="#"
                          data-bs-toggle="dropdown"
                          aria-expanded="false"
                        >
                          <i className="fas fa-ellipsis-v" />
                        </Link>
                        <div className="dropdown-menu notes-menu dropdown-menu-end">
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#edit-note-units"
                          >
                            <span>
                              <i data-feather="edit" />
                            </span>
                            Edit
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#delete-modal"
                          >
                            <span>
                              <i data-feather="trash-2" />
                            </span>
                            Delete
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                          >
                            <span>
                              <i data-feather="star" />
                            </span>
                            Not Important
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#view-note-units"
                          >
                            <span>
                              <i data-feather="eye" />
                            </span>
                            View
                          </Link>
                        </div>
                      </div>
                    </div>
                    <div className="my-3">
                      <h5 className="text-truncate mb-1">
                        <Link to="#">Backup Files EOD</Link>
                      </h5>
                      <p className="mb-3 d-flex align-items-center text-dark">
                        <i className="ti ti-calendar me-1" />
                        20 Jan 2024
                      </p>
                      <p className="text-truncate line-clamb-2 text-wrap">
                        Project files should be took backup before end of the
                        day.
                      </p>
                    </div>
                    <div className="d-flex align-items-center justify-content-between border-top pt-3">
                      <div className="d-flex align-items-center">
                        <Link
                          to="#"
                          className="avatar avatar-md me-2"
                        >
                          <ImageWithBasePath
                            src="./assets/img/profiles/avatar-05.jpg"
                            alt="Profile"
                            className="img-fluid rounded-circle"
                          />
                        </Link>
                        <span className="text-info d-flex align-items-center">
                          <i className="fas fa-square square-rotate fs-10 me-1" />
                          Personal
                        </span>
                      </div>
                      <div className="d-flex align-items-center">
                        <Link to="#" className="me-2">
                          <span>
                            <i className="fas fa-star text-warning" />
                          </span>
                        </Link>
                        <Link to="#">
                          <span>
                            <i className="ti ti-trash text-danger" />
                          </span>
                        </Link>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-4 d-flex">
                <div className="card rounded-3 mb-4 flex-fill">
                  <div className="card-body p-4">
                    <div className="d-flex align-items-center justify-content-between">
                      <span className="badge bg-outline-danger d-inline-flex align-items-center">
                        <i className="fas fa-circle fs-6 me-1" />
                        Low
                      </span>
                      <div>
                        <Link
                          to="#"
                          data-bs-toggle="dropdown"
                          aria-expanded="false"
                        >
                          <i className="fas fa-ellipsis-v" />
                        </Link>
                        <div className="dropdown-menu notes-menu dropdown-menu-end">
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#edit-note-units"
                          >
                            <span>
                              <i data-feather="edit" />
                            </span>
                            Edit
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#delete-modal"
                          >
                            <span>
                              <i data-feather="trash-2" />
                            </span>
                            Delete
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                          >
                            <span>
                              <i data-feather="star" />
                            </span>
                            Not Important
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#view-note-units"
                          >
                            <span>
                              <i data-feather="eye" />
                            </span>
                            View
                          </Link>
                        </div>
                      </div>
                    </div>
                    <div className="my-3">
                      <h5 className="text-truncate mb-1">
                        <Link to="#">Download Server Logs</Link>
                      </h5>
                      <p className="mb-3 d-flex align-items-center text-dark">
                        <i className="ti ti-calendar me-1" />
                        25 Jan 2024
                      </p>
                      <p className="text-truncate line-clamb-2 text-wrap">
                        Server log is a text document that contains a record of
                        all activity.
                      </p>
                    </div>
                    <div className="d-flex align-items-center justify-content-between border-top pt-3">
                      <div className="d-flex align-items-center">
                        <Link
                          to="#"
                          className="avatar avatar-md me-2"
                        >
                          <ImageWithBasePath
                            src="./assets/img/profiles/avatar-06.jpg"
                            alt="Profile"
                            className="img-fluid rounded-circle"
                          />
                        </Link>
                        <span className="text-success d-flex align-items-center">
                          <i className="fas fa-square square-rotate fs-10 me-1" />
                          Work
                        </span>
                      </div>
                      <div className="d-flex align-items-center">
                        <Link to="#" className="me-2">
                          <span>
                            <i className="fas fa-star text-warning" />
                          </span>
                        </Link>
                        <Link to="#">
                          <span>
                            <i className="ti ti-trash text-danger" />
                          </span>
                        </Link>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-4 d-flex">
                <div className="card rounded-3 mb-4 flex-fill">
                  <div className="card-body p-4">
                    <div className="d-flex align-items-center justify-content-between">
                      <span className="badge bg-outline-warning d-inline-flex align-items-center">
                        <i className="fas fa-circle fs-6 me-1" />
                        Medium
                      </span>
                      <div>
                        <Link
                          to="#"
                          data-bs-toggle="dropdown"
                          aria-expanded="false"
                        >
                          <i className="fas fa-ellipsis-v" />
                        </Link>
                        <div className="dropdown-menu notes-menu dropdown-menu-end">
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#edit-note-units"
                          >
                            <span>
                              <i data-feather="edit" />
                            </span>
                            Edit
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#delete-modal"
                          >
                            <span>
                              <i data-feather="trash-2" />
                            </span>
                            Delete
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                          >
                            <span>
                              <i data-feather="star" />
                            </span>
                            Not Important
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#view-note-units"
                          >
                            <span>
                              <i data-feather="eye" />
                            </span>
                            View
                          </Link>
                        </div>
                      </div>
                    </div>
                    <div className="my-3">
                      <h5 className="text-truncate mb-1">
                        <Link to="#">Team meet at Starbucks</Link>
                      </h5>
                      <p className="mb-3 d-flex align-items-center text-dark">
                        <i className="ti ti-calendar me-1" />
                        26 Jan 2024
                      </p>
                      <p className="text-truncate line-clamb-2 text-wrap">
                        Meeting all teamets at Starbucks for identifying them
                        all.
                      </p>
                    </div>
                    <div className="d-flex align-items-center justify-content-between border-top pt-3">
                      <div className="d-flex align-items-center">
                        <Link
                          to="#"
                          className="avatar avatar-md me-2"
                        >
                          <ImageWithBasePath
                            src="./assets/img/profiles/avatar-07.jpg"
                            alt="Profile"
                            className="img-fluid rounded-circle"
                          />
                        </Link>
                        <span className="text-warning d-flex align-items-center">
                          <i className="fas fa-square square-rotate fs-10 me-1" />
                          Social
                        </span>
                      </div>
                      <div className="d-flex align-items-center">
                        <Link to="#" className="me-2">
                          <span>
                            <i className="fas fa-star text-warning" />
                          </span>
                        </Link>
                        <Link to="#">
                          <span>
                            <i className="ti ti-trash text-danger" />
                          </span>
                        </Link>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-4 d-flex">
                <div className="card rounded-3 mb-4 flex-fill">
                  <div className="card-body p-4">
                    <div className="d-flex align-items-center justify-content-between">
                      <span className="badge bg-outline-success d-inline-flex align-items-center">
                        <i className="fas fa-circle fs-6 me-1" />
                        High
                      </span>
                      <div>
                        <Link
                          to="#"
                          data-bs-toggle="dropdown"
                          aria-expanded="false"
                        >
                          <i className="fas fa-ellipsis-v" />
                        </Link>
                        <div className="dropdown-menu notes-menu dropdown-menu-end">
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#edit-note-units"
                          >
                            <span>
                              <i data-feather="edit" />
                            </span>
                            Edit
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#delete-modal"
                          >
                            <span>
                              <i data-feather="trash-2" />
                            </span>
                            Delete
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                          >
                            <span>
                              <i data-feather="star" />
                            </span>
                            Not Important
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#view-note-units"
                          >
                            <span>
                              <i data-feather="eye" />
                            </span>
                            View
                          </Link>
                        </div>
                      </div>
                    </div>
                    <div className="my-3">
                      <h5 className="text-truncate mb-1">
                        <Link to="#">Create a compost pile</Link>
                      </h5>
                      <p className="mb-3 d-flex align-items-center text-dark">
                        <i className="ti ti-calendar me-1" />
                        27 Jan 2024
                      </p>
                      <p className="text-truncate line-clamb-2 text-wrap">
                        Compost pile refers to fruit and vegetable scraps, used
                        tea, coffee grounds etc..
                      </p>
                    </div>
                    <div className="d-flex align-items-center justify-content-between border-top pt-3">
                      <div className="d-flex align-items-center">
                        <Link
                          to="#"
                          className="avatar avatar-md me-2"
                        >
                          <ImageWithBasePath
                            src="./assets/img/profiles/avatar-08.jpg"
                            alt="Profile"
                            className="img-fluid rounded-circle"
                          />
                        </Link>
                        <span className="text-warning d-flex align-items-center">
                          <i className="fas fa-square square-rotate fs-10 me-1" />
                          Social
                        </span>
                      </div>
                      <div className="d-flex align-items-center">
                        <Link to="#" className="me-2">
                          <span>
                            <i className="fas fa-star text-warning" />
                          </span>
                        </Link>
                        <Link to="#">
                          <span>
                            <i className="ti ti-trash text-danger" />
                          </span>
                        </Link>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-4 d-flex">
                <div className="card rounded-3 mb-4 flex-fill">
                  <div className="card-body p-4">
                    <div className="d-flex align-items-center justify-content-between">
                      <span className="badge bg-outline-danger d-inline-flex align-items-center">
                        <i className="fas fa-circle fs-6 me-1" />
                        Low
                      </span>
                      <div>
                        <Link
                          to="#"
                          data-bs-toggle="dropdown"
                          aria-expanded="false"
                        >
                          <i className="fas fa-ellipsis-v" />
                        </Link>
                        <div className="dropdown-menu notes-menu dropdown-menu-end">
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#edit-note-units"
                          >
                            <span>
                              <i data-feather="edit" />
                            </span>
                            Edit
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#delete-modal"
                          >
                            <span>
                              <i data-feather="trash-2" />
                            </span>
                            Delete
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                          >
                            <span>
                              <i data-feather="star" />
                            </span>
                            Not Important
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#view-note-units"
                          >
                            <span>
                              <i data-feather="eye" />
                            </span>
                            View
                          </Link>
                        </div>
                      </div>
                    </div>
                    <div className="my-3">
                      <h5 className="text-truncate mb-1">
                        <Link to="#">
                          Take a hike at a local park
                        </Link>
                      </h5>
                      <p className="mb-3 d-flex align-items-center text-dark">
                        <i className="ti ti-calendar me-1" />
                        28 Jan 2024
                      </p>
                      <p className="text-truncate line-clamb-2 text-wrap">
                        Hiking involves a long energetic walk in a natural
                        environment.
                      </p>
                    </div>
                    <div className="d-flex align-items-center justify-content-between border-top pt-3">
                      <div className="d-flex align-items-center">
                        <Link
                          to="#"
                          className="avatar avatar-md me-2"
                        >
                          <ImageWithBasePath
                            src="./assets/img/profiles/avatar-09.jpg"
                            alt="Profile"
                            className="img-fluid rounded-circle"
                          />
                        </Link>
                        <span className="text-info d-flex align-items-center">
                          <i className="fas fa-square square-rotate fs-10 me-1" />
                          Personal
                        </span>
                      </div>
                      <div className="d-flex align-items-center">
                        <Link to="#" className="me-2">
                          <span>
                            <i className="fas fa-star text-warning" />
                          </span>
                        </Link>
                        <Link to="#">
                          <span>
                            <i className="ti ti-trash text-danger" />
                          </span>
                        </Link>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-4 d-flex">
                <div className="card rounded-3 mb-4 flex-fill">
                  <div className="card-body p-4">
                    <div className="d-flex align-items-center justify-content-between">
                      <span className="badge bg-outline-info d-inline-flex align-items-center">
                        <i className="fas fa-circle fs-6 me-1" />
                        medium
                      </span>
                      <div>
                        <Link
                          to="#"
                          data-bs-toggle="dropdown"
                          aria-expanded="false"
                        >
                          <i className="fas fa-ellipsis-v" />
                        </Link>
                        <div className="dropdown-menu notes-menu dropdown-menu-end">
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#edit-note-units"
                          >
                            <span>
                              <i data-feather="edit" />
                            </span>
                            Edit
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#delete-modal"
                          >
                            <span>
                              <i data-feather="trash-2" />
                            </span>
                            Delete
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                          >
                            <span>
                              <i data-feather="star" />
                            </span>
                            Not Important
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#view-note-units"
                          >
                            <span>
                              <i data-feather="eye" />
                            </span>
                            View
                          </Link>
                        </div>
                      </div>
                    </div>
                    <div className="my-3">
                      <h5 className="text-truncate mb-1">
                        <Link to="#">
                          Research a topic interested
                        </Link>
                      </h5>
                      <p className="mb-3 d-flex align-items-center text-dark">
                        <i className="ti ti-calendar me-1" />
                        28 Jan 2024
                      </p>
                      <p className="text-truncate line-clamb-2 text-wrap">
                        Research a topic interested by listen actively and
                        attentively.
                      </p>
                    </div>
                    <div className="d-flex align-items-center justify-content-between border-top pt-3">
                      <div className="d-flex align-items-center">
                        <Link
                          to="#"
                          className="avatar avatar-md me-2"
                        >
                          <ImageWithBasePath
                            src="./assets/img/profiles/avatar-10.jpg"
                            alt="Profile"
                            className="img-fluid rounded-circle"
                          />
                        </Link>
                        <span className="text-success d-flex align-items-center">
                          <i className="fas fa-square square-rotate fs-10 me-1" />
                          Work
                        </span>
                      </div>
                      <div className="d-flex align-items-center">
                        <Link to="#" className="me-2">
                          <span>
                            <i className="fas fa-star text-warning" />
                          </span>
                        </Link>
                        <Link to="#">
                          <span>
                            <i className="ti ti-trash text-danger" />
                          </span>
                        </Link>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div
            className="tab-pane fade"
            id="v-pills-settings"
            role="tabpanel"
            aria-labelledby="v-pills-settings-tab"
          >
            <div className="row">
              <div className="col-12 d-flex align-items-center justify-content-end">
                <Link to="#" className="btn btn-danger mb-4">
                  <span>
                    {" "}
                    <i className="ti ti-trash f-20 me-2" />
                  </span>
                  Restore all
                </Link>
              </div>
            </div>
            <div className="row">
              <div className="col-md-4 d-flex">
                <div className="card rounded-3 mb-4 flex-fill">
                  <div className="card-body p-4">
                    <div className="d-flex align-items-center justify-content-between">
                      <span className="badge bg-outline-success d-inline-flex align-items-center">
                        <i className="fas fa-circle fs-6 me-1" />
                        High
                      </span>
                      <div>
                        <Link
                          to="#"
                          data-bs-toggle="dropdown"
                          aria-expanded="false"
                        >
                          <i className="fas fa-ellipsis-v" />
                        </Link>
                        <div className="dropdown-menu notes-menu dropdown-menu-end">
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#edit-note-units"
                          >
                            <span>
                              <i data-feather="edit" />
                            </span>
                            Edit
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#delete-modal"
                          >
                            <span>
                              <i data-feather="trash-2" />
                            </span>
                            Delete
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                          >
                            <span>
                              <i data-feather="star" />
                            </span>
                            Not Important
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#view-note-units"
                          >
                            <span>
                              <i data-feather="eye" />
                            </span>
                            View
                          </Link>
                        </div>
                      </div>
                    </div>
                    <div className="my-3">
                      <h5 className="text-truncate mb-1">
                        <Link to="#">Create a compost pile</Link>
                      </h5>
                      <p className="mb-3 d-flex align-items-center text-dark">
                        <i className="ti ti-calendar me-1" />
                        27 Jan 2024
                      </p>
                      <p className="text-truncate line-clamb-2 text-wrap">
                        Compost pile refers to fruit and vegetable scraps, used
                        tea, coffee grounds etc..
                      </p>
                    </div>
                    <div className="d-flex align-items-center justify-content-between border-top pt-3">
                      <div className="d-flex align-items-center">
                        <Link
                          to="#"
                          className="avatar avatar-md me-2"
                        >
                          <ImageWithBasePath
                            src="./assets/img/profiles/avatar-08.jpg"
                            alt="Profile"
                            className="img-fluid rounded-circle"
                          />
                        </Link>
                        <span className="text-warning d-flex align-items-center">
                          <i className="fas fa-square square-rotate fs-10 me-1" />
                          Social
                        </span>
                      </div>
                      <div className="d-flex align-items-center">
                        <Link to="#" className="me-2">
                          <span>
                            <i className="fas fa-star text-warning" />
                          </span>
                        </Link>
                        <Link to="#">
                          <span>
                            <i className="ti ti-trash text-danger" />
                          </span>
                        </Link>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-4 d-flex">
                <div className="card rounded-3 mb-4 flex-fill">
                  <div className="card-body p-4">
                    <div className="d-flex align-items-center justify-content-between">
                      <span className="badge bg-outline-danger d-inline-flex align-items-center">
                        <i className="fas fa-circle fs-6 me-1" />
                        Low
                      </span>
                      <div>
                        <Link
                          to="#"
                          data-bs-toggle="dropdown"
                          aria-expanded="false"
                        >
                          <i className="fas fa-ellipsis-v" />
                        </Link>
                        <div className="dropdown-menu notes-menu dropdown-menu-end">
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#edit-note-units"
                          >
                            <span>
                              <i data-feather="edit" />
                            </span>
                            Edit
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#delete-modal"
                          >
                            <span>
                              <i data-feather="trash-2" />
                            </span>
                            Delete
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                          >
                            <span>
                              <i data-feather="star" />
                            </span>
                            Not Important
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#view-note-units"
                          >
                            <span>
                              <i data-feather="eye" />
                            </span>
                            View
                          </Link>
                        </div>
                      </div>
                    </div>
                    <div className="my-3">
                      <h5 className="text-truncate mb-1">
                        <Link to="#">
                          Take a hike at a local park
                        </Link>
                      </h5>
                      <p className="mb-3 d-flex align-items-center text-dark">
                        <i className="ti ti-calendar me-1" />
                        28 Jan 2024
                      </p>
                      <p className="text-truncate line-clamb-2 text-wrap">
                        Hiking involves a long energetic walk in a natural
                        environment.
                      </p>
                    </div>
                    <div className="d-flex align-items-center justify-content-between border-top pt-3">
                      <div className="d-flex align-items-center">
                        <Link
                          to="#"
                          className="avatar avatar-md me-2"
                        >
                          <ImageWithBasePath
                            src="./assets/img/profiles/avatar-09.jpg"
                            alt="Profile"
                            className="img-fluid rounded-circle"
                          />
                        </Link>
                        <span className="text-info d-flex align-items-center">
                          <i className="fas fa-square square-rotate fs-10 me-1" />
                          Personal
                        </span>
                      </div>
                      <div className="d-flex align-items-center">
                        <Link to="#" className="me-2">
                          <span>
                            <i className="fas fa-star text-warning" />
                          </span>
                        </Link>
                        <Link to="#">
                          <span>
                            <i className="ti ti-trash text-danger" />
                          </span>
                        </Link>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div className="col-md-4 d-flex">
                <div className="card rounded-3 mb-4 flex-fill">
                  <div className="card-body p-4">
                    <div className="d-flex align-items-center justify-content-between">
                      <span className="badge bg-outline-info d-inline-flex align-items-center">
                        <i className="fas fa-circle fs-6 me-1" />
                        medium
                      </span>
                      <div>
                        <Link
                          to="#"
                          data-bs-toggle="dropdown"
                          aria-expanded="false"
                        >
                          <i className="fas fa-ellipsis-v" />
                        </Link>
                        <div className="dropdown-menu notes-menu dropdown-menu-end">
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#edit-note-units"
                          >
                            <span>
                              <i data-feather="edit" />
                            </span>
                            Edit
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#delete-modal"
                          >
                            <span>
                              <i data-feather="trash-2" />
                            </span>
                            Delete
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                          >
                            <span>
                              <i data-feather="star" />
                            </span>
                            Not Important
                          </Link>
                          <Link
                            to="#"
                            className="dropdown-item"
                            data-bs-toggle="modal"
                            data-bs-target="#view-note-units"
                          >
                            <span>
                              <i data-feather="eye" />
                            </span>
                            View
                          </Link>
                        </div>
                      </div>
                    </div>
                    <div className="my-3">
                      <h5 className="text-truncate mb-1">
                        <Link to="#">
                          Research a topic interested
                        </Link>
                      </h5>
                      <p className="mb-3 d-flex align-items-center text-dark">
                        <i className="ti ti-calendar me-1" />
                        28 Jan 2024
                      </p>
                      <p className="text-truncate line-clamb-2 text-wrap">
                        Research a topic interested by listen actively and
                        attentively.
                      </p>
                    </div>
                    <div className="d-flex align-items-center justify-content-between border-top pt-3">
                      <div className="d-flex align-items-center">
                        <Link
                          to="#"
                          className="avatar avatar-md me-2"
                        >
                          <ImageWithBasePath
                            src="./assets/img/profiles/avatar-10.jpg"
                            alt="Profile"
                            className="img-fluid rounded-circle"
                          />
                        </Link>
                        <span className="text-success d-flex align-items-center">
                          <i className="fas fa-square square-rotate fs-10 me-1" />
                          Work
                        </span>
                      </div>
                      <div className="d-flex align-items-center">
                        <Link to="#" className="me-2">
                          <span>
                            <i className="fas fa-star text-warning" />
                          </span>
                        </Link>
                        <Link to="#">
                          <span>
                            <i className="ti ti-trash text-danger" />
                          </span>
                        </Link>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div className="row custom-pagination">
          <div className="col-md-12">
            <div className="paginations d-flex justify-content-end">
              <span>
                <i className="fas fa-chevron-left" />
              </span>
              <ul className="d-flex align-items-center page-wrap">
                <li>
                  <Link to="#" className="active">
                    1
                  </Link>
                </li>
                <li>
                  <Link to="#">2</Link>
                </li>
                <li>
                  <Link to="#">3</Link>
                </li>
                <li>
                  <Link to="#">4</Link>
                </li>
              </ul>
              <span>
                <i className="fas fa-chevron-right" />
              </span>
            </div>
          </div>
        </div>
    </>
  );
};

export default NotesContent;
