
import { Link } from "react-router-dom";
import { all_routes } from "../../../router/all_routes";
import StudentModals from "../studentModals";
import StudentSidebar from "./studentSidebar";
import StudentBreadcrumb from "./studentBreadcrumb";

const StudentResult = () => {
  const routes = all_routes;

  return (
    <>
      {/* Page Wrapper */}
      <div className="page-wrapper">
        <div className="content">
          <div className="row">
            {/* Page Header */}
            <StudentBreadcrumb />
            {/* /Page Header */}
          </div>
          <div className="row">
            {/* Student Information */}
            <StudentSidebar />
            {/* /Student Information */}
            <div className="col-xxl-9 col-xl-8">
              <div className="row">
                <div className="col-md-12">
                  {/* List */}
                  <ul className="nav nav-tabs nav-tabs-bottom mb-4">
                    <li>
                      <Link to={routes.studentDetail} className="nav-link">
                        <i className="ti ti-school me-2" />
                        Student Details
                      </Link>
                    </li>
                    <li>
                      <Link to={routes.studentTimeTable} className="nav-link">
                        <i className="ti ti-table-options me-2" />
                        Time Table
                      </Link>
                    </li>
                    <li>
                      <Link to={routes.studentLeaves} className="nav-link ">
                        <i className="ti ti-calendar-due me-2" />
                        Leave &amp; Attendance
                      </Link>
                    </li>
                    <li>
                      <Link to={routes.studentFees} className="nav-link ">
                        <i className="ti ti-report-money me-2" />
                        Fees
                      </Link>
                    </li>
                    <li>
                      <Link
                        to={routes.studentResult}
                        className="nav-link active"
                      >
                        <i className="ti ti-bookmark-edit me-2" />
                        Exam &amp; Results
                      </Link>
                    </li>
                    <li>
                      <Link to={routes.studentLibrary} className="nav-link">
                        <i className="ti ti-books me-2" />
                        Library
                      </Link>
                    </li>
                  </ul>
                  {/* /List */}
                  <div className="card">
                    <div className="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                      <h4 className="mb-3">Exams &amp; Results</h4>
                      <div className="d-flex align-items-center flex-wrap">
                        <div className="dropdown mb-3 me-2">
                          <Link
                            to="#"
                            className="btn btn-outline-light bg-white dropdown-toggle"
                            data-bs-toggle="dropdown"
                            data-bs-auto-close="outside"
                          >
                            <i className="ti ti-calendar-due me-2" />
                            Year : 2024 / 2025
                          </Link>
                          <ul className="dropdown-menu p-3">
                            <li>
                              <Link to="#" className="dropdown-item rounded-1">
                                Year : 2024 / 2025
                              </Link>
                            </li>
                            <li>
                              <Link to="#" className="dropdown-item rounded-1">
                                Year : 2023 / 2024
                              </Link>
                            </li>
                            <li>
                              <Link to="#" className="dropdown-item rounded-1">
                                Year : 2022 / 2023
                              </Link>
                            </li>
                          </ul>
                        </div>
                      </div>
                    </div>
                    <div className="card-body">
                      <div
                        className="accordions-items-seperate"
                        id="accordionExample"
                      >
                        <div className="accordion-item">
                          <h2 className="accordion-header">
                            <button
                              className="accordion-button"
                              type="button"
                              data-bs-toggle="collapse"
                              data-bs-target="#collapseOne"
                              aria-expanded="true"
                              aria-controls="collapseOne"
                            >
                              <span className="avatar avatar-sm bg-success me-2">
                                <i className="ti ti-checks" />
                              </span>
                              Monthly Test (May)
                            </button>
                          </h2>
                          <div
                            id="collapseOne"
                            className="accordion-collapse collapse show"
                            data-bs-parent="#accordionExample"
                          >
                            <div className="accordion-body">
                              {/* Exam Result List */}
                              <div className="table-responsive">
                                <table className="table">
                                  <thead className="thead-light">
                                    <tr>
                                      <th>Subject</th>
                                      <th>Max Marks</th>
                                      <th>Min Marks</th>
                                      <th>Marks Obtained</th>
                                      <th className="text-end">Result</th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    <tr>
                                      <td>English (150)</td>
                                      <td>100</td>
                                      <td>35</td>
                                      <td>65</td>
                                      <td className="text-end">
                                        <span className="badge badge-soft-success d-inline-flex align-items-center">
                                          <i className="ti ti-circle-filled fs-5 me-1" />
                                          Pass
                                        </span>
                                      </td>
                                    </tr>
                                    <tr>
                                      <td>Mathematics (214)</td>
                                      <td>100</td>
                                      <td>35</td>
                                      <td>73</td>
                                      <td className="text-end">
                                        <span className="badge badge-soft-success d-inline-flex align-items-center">
                                          <i className="ti ti-circle-filled fs-5 me-1" />
                                          Pass
                                        </span>
                                      </td>
                                    </tr>
                                    <tr>
                                      <td>Physics (120)</td>
                                      <td>100</td>
                                      <td>35</td>
                                      <td>55</td>
                                      <td className="text-end">
                                        <span className="badge badge-soft-success d-inline-flex align-items-center">
                                          <i className="ti ti-circle-filled fs-5 me-1" />
                                          Pass
                                        </span>
                                      </td>
                                    </tr>
                                    <tr>
                                      <td>Chemistry (110)</td>
                                      <td>100</td>
                                      <td>35</td>
                                      <td>90</td>
                                      <td className="text-end">
                                        <span className="badge badge-soft-success d-inline-flex align-items-center">
                                          <i className="ti ti-circle-filled fs-5 me-1" />
                                          Pass
                                        </span>
                                      </td>
                                    </tr>
                                    <tr>
                                      <td>Spanish (140)</td>
                                      <td>100</td>
                                      <td>35</td>
                                      <td>88</td>
                                      <td className="text-end">
                                        <span className="badge badge-soft-success d-inline-flex align-items-center">
                                          <i className="ti ti-circle-filled fs-5 me-1" />
                                          Pass
                                        </span>
                                      </td>
                                    </tr>
                                    <tr>
                                      <td className="bg-dark text-white">
                                        Rank : 30
                                      </td>
                                      <td className="bg-dark text-white">
                                        Total : 500
                                      </td>
                                      <td
                                        className="bg-dark text-white"
                                        colSpan={2}
                                      >
                                        Marks Obtained : 395
                                      </td>
                                      <td className="bg-dark text-white text-end">
                                        <div className="d-flex align-items-center justify-content-end">
                                          <span className="me-2">
                                            Percentage : 79.50
                                          </span>
                                          <h6 className="fw-normal text-white">
                                            Result :{" "}
                                            <span className="text-success">
                                              {" "}
                                              Pass
                                            </span>
                                          </h6>
                                        </div>
                                      </td>
                                    </tr>
                                  </tbody>
                                </table>
                              </div>
                              {/* /Exam Resul List */}
                            </div>
                          </div>
                        </div>
                        <div className="accordion-item">
                          <h2 className="accordion-header">
                            <button
                              className="accordion-button collapsed"
                              type="button"
                              data-bs-toggle="collapse"
                              data-bs-target="#collapseTwo"
                              aria-expanded="false"
                              aria-controls="collapseTwo"
                            >
                              <span className="avatar avatar-sm bg-success me-2">
                                <i className="ti ti-checks" />
                              </span>
                              Monthly Test (Apr)
                            </button>
                          </h2>
                          <div
                            id="collapseTwo"
                            className="accordion-collapse collapse"
                            data-bs-parent="#accordionExample"
                          >
                            <div className="accordion-body">
                              {/* Exam Result List */}
                              <div className="table-responsive">
                                <table className="table">
                                  <thead className="thead-light">
                                    <tr>
                                      <th>Subject</th>
                                      <th>Max Marks</th>
                                      <th>Min Marks</th>
                                      <th>Marks Obtained</th>
                                      <th className="text-end">Result</th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    <tr>
                                      <td>English (150)</td>
                                      <td>100</td>
                                      <td>35</td>
                                      <td>59</td>
                                      <td className="text-end">
                                        <span className="badge badge-soft-success d-inline-flex align-items-center">
                                          <i className="ti ti-circle-filled fs-5 me-1" />
                                          Pass
                                        </span>
                                      </td>
                                    </tr>
                                    <tr>
                                      <td>Mathematics (214)</td>
                                      <td>100</td>
                                      <td>35</td>
                                      <td>69</td>
                                      <td className="text-end">
                                        <span className="badge badge-soft-success d-inline-flex align-items-center">
                                          <i className="ti ti-circle-filled fs-5 me-1" />
                                          Pass
                                        </span>
                                      </td>
                                    </tr>
                                    <tr>
                                      <td>Physics (120)</td>
                                      <td>100</td>
                                      <td>35</td>
                                      <td>79</td>
                                      <td className="text-end">
                                        <span className="badge badge-soft-success d-inline-flex align-items-center">
                                          <i className="ti ti-circle-filled fs-5 me-1" />
                                          Pass
                                        </span>
                                      </td>
                                    </tr>
                                    <tr>
                                      <td>Chemistry (110)</td>
                                      <td>100</td>
                                      <td>35</td>
                                      <td>89</td>
                                      <td className="text-end">
                                        <span className="badge badge-soft-success d-inline-flex align-items-center">
                                          <i className="ti ti-circle-filled fs-5 me-1" />
                                          Pass
                                        </span>
                                      </td>
                                    </tr>
                                    <tr>
                                      <td>Spanish (140)</td>
                                      <td>100</td>
                                      <td>35</td>
                                      <td>99</td>
                                      <td className="text-end">
                                        <span className="badge badge-soft-success d-inline-flex align-items-center">
                                          <i className="ti ti-circle-filled fs-5 me-1" />
                                          Pass
                                        </span>
                                      </td>
                                      <td></td>
                                    </tr>
                                    <tr>
                                      <td className="bg-dark text-white">
                                        Rank : 30
                                      </td>
                                      <td className="bg-dark text-white">
                                        Total : 500
                                      </td>
                                      <td
                                        className="bg-dark text-white"
                                        colSpan={2}
                                      >
                                        Marks Obtained : 400
                                      </td>
                                      <td className="bg-dark text-white text-end">
                                        <div className="d-flex align-items-center justify-content-end">
                                          <span className="me-2">
                                            Percentage : 80.50
                                          </span>
                                          <h6 className="fw-normal text-white">
                                            Result :{" "}
                                            <span className="text-success">
                                              {" "}
                                              Pass
                                            </span>
                                          </h6>
                                        </div>
                                      </td>
                                    </tr>
                                  </tbody>
                                </table>
                              </div>
                              {/* /Exam Resul List */}
                            </div>
                          </div>
                        </div>
                        <div className="accordion-item">
                          <h2 className="accordion-header">
                            <button
                              className="accordion-button collapsed"
                              type="button"
                              data-bs-toggle="collapse"
                              data-bs-target="#collapseThree"
                              aria-expanded="false"
                              aria-controls="collapseThree"
                            >
                              <span className="avatar avatar-sm bg-success me-2">
                                <i className="ti ti-checks" />
                              </span>
                              Monthly Test (Mar)
                            </button>
                          </h2>
                          <div
                            id="collapseThree"
                            className="accordion-collapse collapse"
                            data-bs-parent="#accordionExample"
                          >
                            <div className="accordion-body">
                              {/* Exam Result List */}
                              <div className="table-responsive">
                                <table className="table">
                                  <thead className="thead-light">
                                    <tr>
                                      <th>Subject</th>
                                      <th>Max Marks</th>
                                      <th>Min Marks</th>
                                      <th>Marks Obtained</th>
                                      <th className="text-end">Result</th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    <tr>
                                      <td>English (150)</td>
                                      <td>100</td>
                                      <td>35</td>
                                      <td>40</td>
                                      <td className="text-end">
                                        <span className="badge badge-soft-success d-inline-flex align-items-center">
                                          <i className="ti ti-circle-filled fs-5 me-1" />
                                          Pass
                                        </span>
                                      </td>
                                    </tr>
                                    <tr>
                                      <td>Mathematics (214)</td>
                                      <td>100</td>
                                      <td>35</td>
                                      <td>45</td>
                                      <td className="text-end">
                                        <span className="badge badge-soft-success d-inline-flex align-items-center">
                                          <i className="ti ti-circle-filled fs-5 me-1" />
                                          Pass
                                        </span>
                                      </td>
                                    </tr>
                                    <tr>
                                      <td>Physics (120)</td>
                                      <td>100</td>
                                      <td>35</td>
                                      <td>30</td>
                                      <td className="text-end">
                                        <span className="badge badge-soft-success d-inline-flex align-items-center">
                                          <i className="ti ti-circle-filled fs-5 me-1" />
                                          Pass
                                        </span>
                                      </td>
                                    </tr>
                                    <tr>
                                      <td>Chemistry (110)</td>
                                      <td>100</td>
                                      <td>35</td>
                                      <td>28</td>
                                      <td className="text-end">
                                        <span className="badge badge-soft-success d-inline-flex align-items-center">
                                          <i className="ti ti-circle-filled fs-5 me-1" />
                                          Pass
                                        </span>
                                      </td>
                                    </tr>
                                    <tr>
                                      <td>Spanish (140)</td>
                                      <td>100</td>
                                      <td>35</td>
                                      <td>50</td>
                                      <td className="text-end">
                                        <span className="badge badge-soft-success d-inline-flex align-items-center">
                                          <i className="ti ti-circle-filled fs-5 me-1" />
                                          Pass
                                        </span>
                                      </td>
                                    </tr>
                                    <tr>
                                      <td className="bg-dark text-white">
                                        Rank : 30
                                      </td>
                                      <td className="bg-dark text-white">
                                        Total : 500
                                      </td>
                                      <td
                                        className="bg-dark text-white"
                                        colSpan={2}
                                      >
                                        Marks Obtained : 250
                                      </td>
                                      <td className="bg-dark text-white text-end">
                                        <div className="d-flex align-items-center justify-content-end">
                                          <span className="me-2">
                                            Percentage : 50
                                          </span>
                                          <h6 className="text-white fw-normal">
                                            Result :{" "}
                                            <span className="text-danger">
                                              {" "}
                                              Fail
                                            </span>
                                          </h6>
                                        </div>
                                      </td>
                                    </tr>
                                  </tbody>
                                </table>
                              </div>
                              {/* /Exam Resul List */}
                            </div>
                          </div>
                        </div>
                        <div className="accordion-item">
                          <h2 className="accordion-header">
                            <button
                              className="accordion-button collapsed"
                              type="button"
                              data-bs-toggle="collapse"
                              data-bs-target="#collapseFour"
                              aria-expanded="false"
                              aria-controls="collapseFour"
                            >
                              <span className="avatar avatar-sm bg-success me-2">
                                <i className="ti ti-checks" />
                              </span>
                              Monthly Test (Feb)
                            </button>
                          </h2>
                          <div
                            id="collapseFour"
                            className="accordion-collapse collapse"
                            data-bs-parent="#accordionExample"
                          >
                            <div className="accordion-body">
                              {/* Exam Result List */}
                              <div className="table-responsive">
                                <table className="table">
                                  <thead className="thead-light">
                                    <tr>
                                      <th>Subject</th>
                                      <th>Max Marks</th>
                                      <th>Min Marks</th>
                                      <th>Marks Obtained</th>
                                      <th className="text-end">Result</th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    <tr>
                                      <td>English (150)</td>
                                      <td>100</td>
                                      <td>35</td>
                                      <td>40</td>
                                      <td className="text-end">
                                        <span className="badge badge-soft-success d-inline-flex align-items-center">
                                          <i className="ti ti-circle-filled fs-5 me-1" />
                                          Pass
                                        </span>
                                      </td>
                                    </tr>
                                    <tr>
                                      <td>Mathematics (214)</td>
                                      <td>100</td>
                                      <td>35</td>
                                      <td>45</td>
                                      <td className="text-end">
                                        <span className="badge badge-soft-success d-inline-flex align-items-center">
                                          <i className="ti ti-circle-filled fs-5 me-1" />
                                          Pass
                                        </span>
                                      </td>
                                    </tr>
                                    <tr>
                                      <td>Physics (120)</td>
                                      <td>100</td>
                                      <td>35</td>
                                      <td>30</td>
                                      <td className="text-end">
                                        <span className="badge badge-soft-danger d-inline-flex align-items-center">
                                          <i className="ti ti-circle-filled fs-5 me-1" />
                                          Fail
                                        </span>
                                      </td>
                                    </tr>
                                    <tr>
                                      <td>Chemistry (110)</td>
                                      <td>100</td>
                                      <td>35</td>
                                      <td>28</td>
                                      <td className="text-end">
                                        <span className="badge badge-soft-danger d-inline-flex align-items-center ">
                                          <i className="ti ti-circle-filled fs-5 me-1" />
                                          Fail
                                        </span>
                                      </td>
                                    </tr>
                                    <tr>
                                      <td>Spanish (140)</td>
                                      <td>100</td>
                                      <td>35</td>
                                      <td>50</td>
                                      <td className="text-end">
                                        <span className="badge badge-soft-success d-inline-flex align-items-center">
                                          <i className="ti ti-circle-filled fs-5 me-1" />
                                          Pass
                                        </span>
                                      </td>
                                    </tr>
                                    <tr>
                                      <td className="bg-dark text-white">
                                        Rank : 30
                                      </td>
                                      <td className="bg-dark text-white">
                                        Total : 500
                                      </td>
                                      <td
                                        className="bg-dark text-white"
                                        colSpan={2}
                                      >
                                        Marks Obtained : 250
                                      </td>
                                      <td className="bg-dark text-white text-end">
                                        <div className="d-flex align-items-center justify-content-end">
                                          <span className="me-2">
                                            Percentage : 50
                                          </span>
                                          <h6 className="text-white">
                                            Result :{" "}
                                            <span className="text-danger">
                                              {" "}
                                              Fail
                                            </span>
                                          </h6>
                                        </div>
                                      </td>
                                    </tr>
                                  </tbody>
                                </table>
                              </div>
                              {/* /Exam Resul List */}
                            </div>
                          </div>
                        </div>
                        <div className="accordion-item">
                          <h2 className="accordion-header">
                            <button
                              className="accordion-button collapsed"
                              type="button"
                              data-bs-toggle="collapse"
                              data-bs-target="#collapseFive"
                              aria-expanded="false"
                              aria-controls="collapseFive"
                            >
                              <span className="avatar avatar-sm bg-success me-2">
                                <i className="ti ti-checks" />
                              </span>
                              Monthly Test (Jan)
                            </button>
                          </h2>
                          <div
                            id="collapseFive"
                            className="accordion-collapse collapse"
                            data-bs-parent="#accordionExample"
                          >
                            <div className="accordion-body">
                              {/* Exam Result List */}
                              <div className="table-responsive">
                                <table className="table">
                                  <thead className="thead-light">
                                    <tr>
                                      <th>Subject</th>
                                      <th>Max Marks</th>
                                      <th>Min Marks</th>
                                      <th>Marks Obtained</th>
                                      <th className="text-end">Result</th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    <tr>
                                      <td>English (150)</td>
                                      <td>100</td>
                                      <td>35</td>
                                      <td>59</td>
                                      <td className="text-end">
                                        <span className="badge badge-soft-success d-inline-flex align-items-center">
                                          <i className="ti ti-circle-filled fs-5 me-1" />
                                          Pass
                                        </span>
                                      </td>
                                    </tr>
                                    <tr>
                                      <td>Mathematics (214)</td>
                                      <td>100</td>
                                      <td>35</td>
                                      <td>69</td>
                                      <td className="text-end">
                                        <span className="badge badge-soft-success d-inline-flex align-items-center">
                                          <i className="ti ti-circle-filled fs-5 me-1" />
                                          Pass
                                        </span>
                                      </td>
                                    </tr>
                                    <tr>
                                      <td>Physics (120)</td>
                                      <td>100</td>
                                      <td>35</td>
                                      <td>79</td>
                                      <td className="text-end">
                                        <span className="badge badge-soft-success d-inline-flex align-items-center">
                                          <i className="ti ti-circle-filled fs-5 me-1" />
                                          Pass
                                        </span>
                                      </td>
                                    </tr>
                                    <tr>
                                      <td>Chemistry (110)</td>
                                      <td>100</td>
                                      <td>35</td>
                                      <td>89</td>
                                      <td className="text-end">
                                        <span className="badge badge-soft-success d-inline-flex align-items-center">
                                          <i className="ti ti-circle-filled fs-5 me-1" />
                                          Pass
                                        </span>
                                      </td>
                                    </tr>
                                    <tr>
                                      <td>Spanish (140)</td>
                                      <td>100</td>
                                      <td>35</td>
                                      <td>99</td>
                                      <td className="text-end">
                                        <span className="badge badge-soft-success d-inline-flex align-items-center">
                                          <i className="ti ti-circle-filled fs-5 me-1" />
                                          Pass
                                        </span>
                                      </td>
                                    </tr>
                                    <tr>
                                      <td className="bg-dark text-white">
                                        Rank : 30
                                      </td>
                                      <td className="bg-dark text-white">
                                        Total : 500
                                      </td>
                                      <td
                                        className="bg-dark text-white"
                                        colSpan={2}
                                      >
                                        Marks Obtained : 400
                                      </td>
                                      <td className="bg-dark text-white text-end">
                                        <div className="d-flex align-items-center justify-content-end">
                                          <span className="me-2">
                                            Percentage : 80.50
                                          </span>
                                          <h6 className="fw-normal text-white">
                                            Result :{" "}
                                            <span className="text-success">
                                              {" "}
                                              Pass
                                            </span>
                                          </h6>
                                        </div>
                                      </td>
                                    </tr>
                                  </tbody>
                                </table>
                              </div>
                              {/* /Exam Resul List */}
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      {/* /Page Wrapper */}
      <StudentModals />
    </>
  );
};

export default StudentResult;
