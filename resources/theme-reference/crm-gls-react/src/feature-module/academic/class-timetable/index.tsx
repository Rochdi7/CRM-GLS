import  { useRef, useState } from "react";
import ImageWithBasePath from "../../../core/common/imageWithBasePath";
import {
  classduration,
  classSection,
  classSylabus,
  language,
  period,
  routinename,
  subjectGroup,
  teacher,
  Time,
  Timeto,
} from "../../../core/common/selectoption/selectoption";
import CommonSelect from "../../../core/common/commonSelect";
import { Link } from "react-router-dom";
import { all_routes } from "../../router/all_routes";
import TooltipOption from "../../../core/common/tooltipOption";

const ClassTimetable = () => {
  const routes = all_routes;

  const dropdownMenuRef = useRef<HTMLDivElement | null>(null);
  const handleApplyClick = () => {
    if (dropdownMenuRef.current) {
      dropdownMenuRef.current.classList.remove("show");
    }
  };
  const [newContents, setNewContents] = useState<number[]>([0]);
  const [tuesdayContents, settuesdayContents] = useState<number[]>([0]);
  const [wednessdayContents, setwednessdayContents] = useState<number[]>([0]);
  const [thursdayContents, setthursdayContents] = useState<number[]>([0]);
  const [fridayContents, setfridayContents] = useState<number[]>([0]);
  const addNewContent = () => {
    setNewContents([...newContents, newContents.length]);
  };
  const addTuesdayContent = () => {
    settuesdayContents([...tuesdayContents, tuesdayContents.length]);
  };
  const addwednessdayContent = () => {
    setwednessdayContents([...wednessdayContents, wednessdayContents.length]);
  };
  const addthursdayContents = () => {
    setthursdayContents([...thursdayContents, thursdayContents.length]);
  };
  const addfridayContents = () => {
    setfridayContents([...fridayContents, fridayContents.length]);
  };
  const removeContent = (index:any) => {
    setNewContents(newContents.filter((_, i) => i !== index));
  };
  const removetuesdayContent = (index:any) => {
    settuesdayContents(tuesdayContents.filter((_, i) => i !== index));
  };
  const  removewednessdayContent = (index:any) => {
    setwednessdayContents(wednessdayContents.filter((_, i) => i !== index));
  };
  const  removethursdayContents = (index:any) => {
    setthursdayContents(thursdayContents.filter((_, i) => i !== index));
  };
  const  removefridayContents = (index:any) => {
    setfridayContents(fridayContents.filter((_, i) => i !== index));
  };
  return (
    <div>
      {/* Page Wrapper */}
      <div className="page-wrapper">
        <div className="content content-two">
          <div className="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div className="my-auto mb-2">
              <h3 className="page-title mb-1">Time Table</h3>
              <nav>
                <ol className="breadcrumb mb-0">
                  <li className="breadcrumb-item">
                    <Link to={routes.adminDashboard}>Dashboard</Link>
                  </li>
                  <li className="breadcrumb-item">Academic</li>
                  <li className="breadcrumb-item active" aria-current="page">
                    Time Table
                  </li>
                </ol>
              </nav>
            </div>
            <div className="d-flex my-xl-auto right-content align-items-center flex-wrap">
            <TooltipOption />
              <div className="mb-2">
                <Link
                  to="#"
                  className="btn btn-primary d-flex align-items-center"
                  data-bs-toggle="modal"
                  data-bs-target="#add_time_table"
                >
                  <i className="ti ti-square-rounded-plus me-2" />
                  Add Time Table
                </Link>
              </div>
            </div>
          </div>
          <div className="card">
            <div className="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
              <h4 className="mb-3">Time Table</h4>
              <div className="d-flex align-items-center flex-wrap">
                <div className="dropdown mb-3 me-2">
                  <Link
                    to="#"
                    className="btn btn-outline-light bg-white dropdown-toggle"
                    data-bs-toggle="dropdown"
                    data-bs-auto-close="outside"
                    aria-expanded="false"
                  >
                    <i className="ti ti-filter me-2" />
                    Filter
                  </Link>
                  <div
                    className="dropdown-menu drop-width"
                    ref={dropdownMenuRef}
                  >
                    <form>
                      <div className="d-flex align-items-center border-bottom p-3">
                        <h4>Filter</h4>
                      </div>
                      <div className="p-3 pb-0 border-bottom">
                        <div className="row">
                          <div className="col-md-12">
                            <div className="mb-3">
                              <label className="form-label">Class</label>

                              <CommonSelect
                                className="select"
                                options={classSylabus}
                              />
                            </div>
                          </div>
                          <div className="col-md-12">
                            <div className="mb-3">
                              <label className="form-label">Section</label>
                              <CommonSelect
                                className="select"
                                options={classSection}
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                      <div className="p-3 d-flex align-items-center justify-content-end">
                        <Link to="#" className="btn btn-light me-3">
                          Reset
                        </Link>
                        <Link
                          to="#"
                          className="btn btn-primary"
                          onClick={handleApplyClick}
                        >
                          Apply
                        </Link>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
            <div className="card-body pb-0">
              <div className="d-flex flex-nowrap overflow-auto">
                <div className="d-flex flex-column me-4 flex-fill">
                  <div className="mb-3">
                    <h6>Monday</h6>
                  </div>
                  <div className="bg-transparent-danger rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      09:00 - 09:45 AM
                    </p>
                    <p className="text-dark">Subject : Maths</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-07.jpg"
                            alt="Img"
                          />
                        </span>
                        Jacquelin
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-primary rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      09:45 - 10:30 AM
                    </p>
                    <p className="text-dark">Subject : English</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-03.jpg"
                            alt="Img"
                          />
                        </span>
                        Hellana
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-success rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      10:45 - 11:30 AM
                    </p>
                    <p className="text-dark">Subject : Computer</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-02.jpg"
                            alt="Img"
                          />
                        </span>
                        Daniel
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-pending rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      11:30 - 12:15 PM
                    </p>
                    <p className="text-dark">Subject : Spanish</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-03.jpg"
                            alt="Img"
                          />
                        </span>
                        Erickson
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-info rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      01:30 - 02:15 PM
                    </p>
                    <p className="text-dark">Subject : Science</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-05.jpg"
                            alt="Img"
                          />
                        </span>
                        Morgan
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-light rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      02:15 - 03:00 PM
                    </p>
                    <p className="text-dark">Subject : Chemistry</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-06.jpg"
                            alt="Img"
                          />
                        </span>
                        Aaron
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-warning rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      03:15 - 04:00 PM
                    </p>
                    <p className="text-dark">Subject : Physics</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-01.jpg"
                            alt="Img"
                          />
                        </span>
                        Teresa
                      </Link>
                    </div>
                  </div>
                </div>
                <div className="d-flex flex-column me-4 flex-fill">
                  <div className="mb-3">
                    <h6>Tuesday</h6>
                  </div>
                  <div className="bg-transparent-pending rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      09:00 - 09:45 AM
                    </p>
                    <p className="text-dark">Subject : Spanish</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-03.jpg"
                            alt="Img"
                          />
                        </span>
                        Erickson
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-warning rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      09:45 - 10:30 AM
                    </p>
                    <p className="text-dark">Subject : Physics</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-01.jpg"
                            alt="Img"
                          />
                        </span>
                        Teresa
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-light rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      10:45 - 11:30 AM
                    </p>
                    <p className="text-dark">Subject : Chemistry</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-06.jpg"
                            alt="Img"
                          />
                        </span>
                        Aaron
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-danger rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      11:30 - 12:15 PM
                    </p>
                    <p className="text-dark">Subject : Maths</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-07.jpg"
                            alt="Img"
                          />
                        </span>
                        Jacquelin
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-success rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      01:30 - 02:15 PM
                    </p>
                    <p className="text-dark">Subject : Computer</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-02.jpg"
                            alt="Img"
                          />
                        </span>
                        Daniel
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-primary rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      02:15 - 03:00 PM
                    </p>
                    <p className="text-dark">Subject : English</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-03.jpg"
                            alt="Img"
                          />
                        </span>
                        Hellana
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-info rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      03:15 - 04:00 PM
                    </p>
                    <p className="text-dark">Subject : Science</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-05.jpg"
                            alt="Img"
                          />
                        </span>
                        Morgan
                      </Link>
                    </div>
                  </div>
                </div>
                <div className="d-flex flex-column me-4 flex-fill">
                  <div className="mb-3">
                    <h6>Wednesday</h6>
                  </div>
                  <div className="bg-transparent-success rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      09:00 - 09:45 AM
                    </p>
                    <p className="text-dark">Subject : Computer</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-02.jpg"
                            alt="Img"
                          />
                        </span>
                        Daniel
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-info rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      09:45 - 10:30 AM
                    </p>
                    <p className="text-dark">Subject : Science</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-05.jpg"
                            alt="Img"
                          />
                        </span>
                        Morgan
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-danger rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      10:45 - 11:30 AM
                    </p>
                    <p className="text-dark">Subject : Maths</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-07.jpg"
                            alt="Img"
                          />
                        </span>
                        Jacquelin
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-light rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      11:30 - 12:15 PM
                    </p>
                    <p className="text-dark">Subject : Chemistry</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-06.jpg"
                            alt="Img"
                          />
                        </span>
                        Aaron
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-warning rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      01:30 - 02:15 PM
                    </p>
                    <p className="text-dark">Subject : Physics</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-01.jpg"
                            alt="Img"
                          />
                        </span>
                        Teresa
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-primary rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      02:15 - 03:00 PM
                    </p>
                    <p className="text-dark">Subject : English</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-03.jpg"
                            alt="Img"
                          />
                        </span>
                        Hellana
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-pending rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      03:15 - 04:00 PM
                    </p>
                    <p className="text-dark">Subject : Spanish</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-03.jpg"
                            alt="Img"
                          />
                        </span>
                        Erickson
                      </Link>
                    </div>
                  </div>
                </div>
                <div className="d-flex flex-column me-4 flex-fill">
                  <div className="mb-3">
                    <h6>Thursday</h6>
                  </div>
                  <div className="bg-transparent-warning rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      09:00 - 09:45 AM
                    </p>
                    <p className="text-dark">Subject : Physics</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-01.jpg"
                            alt="Img"
                          />
                        </span>
                        Teresa
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-success rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      09:45 - 10:30 AM
                    </p>
                    <p className="text-dark">Subject : Computer</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-02.jpg"
                            alt="Img"
                          />
                        </span>
                        Daniel
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-primary rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      10:45 - 11:30 AM
                    </p>
                    <p className="text-dark">Subject : English</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-03.jpg"
                            alt="Img"
                          />
                        </span>
                        Hellana
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-info rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      11:30 - 12:15 PM
                    </p>
                    <p className="text-dark">Subject : Science</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-05.jpg"
                            alt="Img"
                          />
                        </span>
                        Morgan
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-pending rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      01:30 - 02:15 PM
                    </p>
                    <p className="text-dark">Subject : Spanish</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-03.jpg"
                            alt="Img"
                          />
                        </span>
                        Erickson
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-light rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      02:15 - 03:00 PM
                    </p>
                    <p className="text-dark">Subject : Chemistry</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-06.jpg"
                            alt="Img"
                          />
                        </span>
                        Aaron
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-danger rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      03:15 - 04:00 PM
                    </p>
                    <p className="text-dark">Subject : Maths</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-07.jpg"
                            alt="Img"
                          />
                        </span>
                        Jacquelin
                      </Link>
                    </div>
                  </div>
                </div>
                <div className="d-flex flex-column me-4 flex-fill">
                  <div className="mb-3">
                    <h6>Friday</h6>
                  </div>
                  <div className="bg-transparent-primary rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      09:00 - 09:45 AM
                    </p>
                    <p className="text-dark">Subject : English</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-03.jpg"
                            alt="Img"
                          />
                        </span>
                        Hellana
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-pending rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      09:45 - 10:30 AM
                    </p>
                    <p className="text-dark">Subject : Spanish</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-03.jpg"
                            alt="Img"
                          />
                        </span>
                        Erickson
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-warning rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      10:45 - 11:30 AM
                    </p>
                    <p className="text-dark">Subject : Physics</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-01.jpg"
                            alt="Img"
                          />
                        </span>
                        Teresa
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-light rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      11:30 - 12:15 PM
                    </p>
                    <p className="text-dark">Subject : Chemistry</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-06.jpg"
                            alt="Img"
                          />
                        </span>
                        Aaron
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-danger rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      01:30 - 02:15 PM
                    </p>
                    <p className="text-dark">Subject : Maths</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-07.jpg"
                            alt="Img"
                          />
                        </span>
                        Jacquelin
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-success rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      02:15 - 3:00 PM
                    </p>
                    <p className="text-dark">Subject : Computer</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-02.jpg"
                            alt="Img"
                          />
                        </span>
                        Daniel
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-info rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      03:15 - 04:00 PM
                    </p>
                    <p className="text-dark">Subject : Science</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-05.jpg"
                            alt="Img"
                          />
                        </span>
                        Morgan
                      </Link>
                    </div>
                  </div>
                </div>
                <div className="d-flex flex-column flex-fill">
                  <div className="mb-3">
                    <h6>Saturday</h6>
                  </div>
                  <div className="bg-transparent-primary rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      09:00 - 09:45 AM
                    </p>
                    <p className="text-dark">Subject : English</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-03.jpg"
                            alt="Img"
                          />
                        </span>
                        Hellana
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-pending rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      09:45 - 10:30 AM
                    </p>
                    <p className="text-dark">Subject : Spanish</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-03.jpg"
                            alt="Img"
                          />
                        </span>
                        Erickson
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-warning rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      10:45 - 11:30 AM
                    </p>
                    <p className="text-dark">Subject : Physics</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-01.jpg"
                            alt="Img"
                          />
                        </span>
                        Teresa
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-light rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      11:30 - 12:15 PM
                    </p>
                    <p className="text-dark">Subject : Chemistry</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-06.jpg"
                            alt="Img"
                          />
                        </span>
                        Aaron
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-danger rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      01:30 - 02:15 PM
                    </p>
                    <p className="text-dark">Subject : Maths</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-07.jpg"
                            alt="Img"
                          />
                        </span>
                        Jacquelin
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-success rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      02:15 - 3:00 PM
                    </p>
                    <p className="text-dark">Subject : Computer</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-02.jpg"
                            alt="Img"
                          />
                        </span>
                        Daniel
                      </Link>
                    </div>
                  </div>
                  <div className="bg-transparent-info rounded p-3 mb-4">
                    <p className="d-flex align-items-center text-nowrap mb-1">
                      <i className="ti ti-clock me-1" />
                      03:15 - 04:00 PM
                    </p>
                    <p className="text-dark">Subject : Science</p>
                    <div className="bg-white rounded p-1 mt-3">
                      <Link
                        to={routes.teacherDetails}
                        className="text-muted d-flex align-items-center"
                      >
                        <span className="avatar avatar-sm me-2">
                          <ImageWithBasePath
                            src="assets/img/teachers/teacher-05.jpg"
                            alt="Img"
                          />
                        </span>
                        Morgan
                      </Link>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div className="card-footer border-0 pb-0">
              <div className="row">
                <div className="col-lg-4 col-xxl-4 col-xl-4 d-flex">
                  <div className="card flex-fill">
                    <div className="card-body">
                      <span className="bg-primary badge badge-sm mb-2">
                        Morning Break
                      </span>
                      <p className="text-dark">
                        <i className="ti ti-clock me-1" />
                        10:30 to 10 :45 AM
                      </p>
                    </div>
                  </div>
                </div>
                <div className="col-lg-4 col-xxl-3 d-flex">
                  <div className="card flex-fill">
                    <div className="card-body">
                      <span className="bg-warning badge badge-sm mb-2">
                        Lunch
                      </span>
                      <p className="text-dark">
                        <i className="ti ti-clock me-1" />
                        10:30 to 10 :45 AM
                      </p>
                    </div>
                  </div>
                </div>
                <div className="col-lg-4 col-xxl-3 d-flex">
                  <div className="card flex-fill">
                    <div className="card-body">
                      <span className="bg-info badge badge-sm mb-2">
                        Evening Break
                      </span>
                      <p className="text-dark">
                        <i className="ti ti-clock me-1" />
                        03:30 PM to 03:45 PM
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      {/* /Page Wrapper */}
      <>
        {/* Add Class Time Table */}
        <div className="modal fade" id="add_time_table">
          <div className="modal-dialog modal-dialog-centered modal-xl">
            <div className="modal-content">
              <div className="modal-header">
                <h4 className="modal-title">Add Time Table</h4>
                <button
                  type="button"
                  className="btn-close custom-btn-close"
                  data-bs-dismiss="modal"
                  aria-label="Close"
                >
                  <i className="ti ti-x" />
                </button>
              </div>
              <form >
                <div className="modal-body">
                  <div className="row">
                    <div className="col-lg-4">
                      <div className="mb-3">
                        <label className="form-label">Class</label>
                        <input type="text" className="form-control" />
                      </div>
                    </div>
                    <div className="col-lg-4">
                      <div className="mb-3">
                        <label className="form-label">Section</label>
                        <CommonSelect
                          className="select"
                          options={classSection}
                        />
                      </div>
                    </div>
                    <div className="col-lg-4">
                      <div className="mb-3">
                        <label className="form-label">Subject Group</label>
                        <CommonSelect
                          className="select"
                          options={subjectGroup}
                        />
                      </div>
                    </div>
                    <div className="col-lg-4">
                      <div className="mb-3">
                        <label className="form-label">Period Start Time</label>
                        <CommonSelect className="select" options={period} />
                      </div>
                    </div>
                    <div className="col-lg-4">
                      <div className="mb-3">
                        <label className="form-label">Duration(min)</label>

                        <CommonSelect
                          className="select"
                          options={classduration}
                        />
                      </div>
                    </div>
                  </div>
                  <div className="add-more-timetable">
                    <ul
                      className="tab-links nav nav-pills"
                      id="pills-tab2"
                      role="tablist"
                    >
                      <li
                        className="nav-link active"
                        id="pills-monday-tab"
                        data-bs-toggle="pill"
                        data-bs-target="#pills-monday"
                        role="tab"
                        aria-controls="pills-monday"
                        aria-selected="true"
                      >
                        <Link to="#">Monday</Link>
                      </li>
                      <li
                        className="nav-link"
                        id="pills-tuesday-tab"
                        data-bs-toggle="pill"
                        data-bs-target="#pills-tuesday"
                        role="tab"
                        aria-controls="pills-tuesday"
                        aria-selected="false"
                        tabIndex={-1}
                      >
                        <Link to="#">Tuesday</Link>
                      </li>
                      <li
                        className="nav-link"
                        id="pills-wednesday-tab"
                        data-bs-toggle="pill"
                        data-bs-target="#pills-wednesday"
                        role="tab"
                        aria-controls="pills-wednesday"
                        aria-selected="false"
                        tabIndex={-1}
                      >
                        <Link to="#">Wednesday</Link>
                      </li>
                      <li
                        className="nav-link"
                        id="pills-thursday-tab"
                        data-bs-toggle="pill"
                        data-bs-target="#pills-thursday"
                        role="tab"
                        aria-controls="pills-thursday"
                        aria-selected="false"
                        tabIndex={-1}
                      >
                        <Link to="#">Thursday</Link>
                      </li>
                      <li
                        className="nav-link"
                        id="pills-friday-tab"
                        data-bs-toggle="pill"
                        data-bs-target="#pills-friday"
                        role="tab"
                        aria-controls="pills-friday"
                        aria-selected="false"
                        tabIndex={-1}
                      >
                        <Link to="#">Friday</Link>
                      </li>
                    </ul>
                    <div className="tab-content pt-0 dashboard-tab">
                      <div
                        className="tab-pane fade show active"
                        id="pills-monday"
                        role="tabpanel"
                        aria-labelledby="pills-monday-tab"
                      >
                        {newContents.map((_, index) => (
                        <div className="add-timetable-row">
                          <div className="row timetable-count">
                            <div className="col-lg-3">
                              <div className="mb-3">
                                <label className="form-label">Subject</label>
                                <CommonSelect
                                  className="select"
                                  options={teacher}
                                />
                              </div>
                            </div>
                            <div className="col-lg-3">
                              <div className="mb-3">
                                <label className="form-label">Teacher</label>
                                <CommonSelect
                                  className="select"
                                  options={language}
                                />
                              </div>
                            </div>
                            <div className="col-lg-3">
                              <div className="mb-3">
                                <label className="form-label">Time From</label>
                                <CommonSelect
                                  className="select"
                                  options={Time}
                                />
                              </div>
                            </div>
                            <div className="col-lg-3">
                              <div className="d-flex align-items-end">
                                <div className="mb-3 flex-fill">
                                  <label className="form-label">Time To</label>
                                  <CommonSelect
                                    className="select"
                                    options={Timeto}
                                  />
                                </div>
                                {newContents.length > 1 && (
                                <div className="mb-3 ms-2">
                                  <Link to="#" className="delete-time-table"  onClick={() => removeContent(index)}>
                                    <i className="ti ti-trash" />
                                  </Link>
                                </div>
                                 )}
                              </div>
                            </div>
                          </div>
                        </div>
                          ))}
                        <div>
                          <Link
                            to="#"
                            className="btn btn-primary add-new-timetable"
                            onClick={addNewContent}
                          >
                            <i className="ti ti-square-rounded-plus-filled me-2" />
                            Add New
                          </Link>
                        </div>
                      </div>
                      <div
                        className="tab-pane fade"
                        id="pills-tuesday"
                        role="tabpanel"
                        aria-labelledby="pills-tuesday-tab"
                      >
                         {tuesdayContents.map((_, index) => (
                        <div className="add-timetable-row">
                          <div className="row timetable-count">
                            <div className="col-lg-3">
                              <div className="mb-3">
                                <label className="form-label">Subject</label>
                                <CommonSelect
                                  className="select"
                                  options={language}
                                />
                              </div>
                            </div>
                            <div className="col-lg-3">
                              <div className="mb-3">
                                <label className="form-label">Teacher</label>
                                <CommonSelect
                                  className="select"
                                  options={teacher}
                                />
                              </div>
                            </div>
                            <div className="col-lg-3">
                              <div className="mb-3">
                                <label className="form-label">Time From</label>
                                <CommonSelect
                                  className="select"
                                  options={Time}
                                />
                              </div>
                            </div>
                            <div className="col-lg-3">
                              <div className="d-flex align-items-end">
                                <div className="mb-3 flex-fill">
                                  <label className="form-label">Time To</label>
                                  <CommonSelect
                                    className="select"
                                    options={Timeto}
                                  />
                                </div>
                                {tuesdayContents.length > 1 && (
                                <div className="mb-3 ms-2">
                                  <Link to="#" className="delete-time-table" onClick={() => removetuesdayContent(index)}>
                                    <i className="ti ti-trash" />
                                  </Link>
                                </div>
                                  )}
                              </div>
                            </div>
                          </div>
                        </div>
                          ))}
                        <div>
                          <Link
                            to="#"   onClick={addTuesdayContent}
                            className="btn btn-primary add-new-timetable"
                          >
                            <i className="ti ti-square-rounded-plus-filled me-2" />
                            Add New
                          </Link>
                        </div>
                      </div>
                      <div
                        className="tab-pane fade"
                        id="pills-wednesday"
                        role="tabpanel"
                        aria-labelledby="pills-wednesday-tab"
                      >
                        {wednessdayContents.map((_, index) => (
                        <div className="add-timetable-row">
                          <div className="row timetable-count">
                            <div className="col-lg-3">
                              <div className="mb-3">
                                <label className="form-label">Subject</label>
                                <CommonSelect
                                  className="select"
                                  options={language}
                                />
                              </div>
                            </div>
                            <div className="col-lg-3">
                              <div className="mb-3">
                                <label className="form-label">Teacher</label>
                                <CommonSelect
                                  className="select"
                                  options={teacher}
                                />
                              </div>
                            </div>
                            <div className="col-lg-3">
                              <div className="mb-3">
                                <label className="form-label">Time From</label>

                                <CommonSelect
                                  className="select"
                                  options={Time}
                                />
                              </div>
                            </div>
                            <div className="col-lg-3">
                              <div className="d-flex align-items-end">
                                <div className="mb-3 flex-fill">
                                  <label className="form-label">Time To</label>
                                  <CommonSelect
                                    className="select"
                                    options={Time}
                                  />
                                </div>
                                {wednessdayContents.length > 1 && (
                                <div className="mb-3 ms-2">
                                  <Link to="#" className="delete-time-table" onClick={() => removewednessdayContent(index)}>
                                    <i className="ti ti-trash" />
                                  </Link>
                                </div>
                                )}
                              </div>
                            </div>
                          </div>
                        </div>
                         ))}
                        <div>
                          
                          <Link
                            to="#"
                            className="btn btn-primary add-new-timetable"  onClick={addwednessdayContent}
                          >
                            <i className="ti ti-square-rounded-plus-filled me-2" />
                            Add New
                          </Link>
                        </div>
                      </div>
                      <div
                        className="tab-pane fade"
                        id="pills-thursday"
                        role="tabpanel"
                        aria-labelledby="pills-thursday-tab"
                      >
                         {thursdayContents.map((_, index) => (
                        <div className="add-timetable-row">
                          <div className="row timetable-count">
                            <div className="col-lg-3">
                              <div className="mb-3">
                                <label className="form-label">Subject</label>
                                <CommonSelect
                                  className="select"
                                  options={language}
                                />
                              </div>
                            </div>
                            <div className="col-lg-3">
                              <div className="mb-3">
                                <label className="form-label">Teacher</label>
                                <CommonSelect
                                  className="select"
                                  options={routinename}
                                />
                              </div>
                            </div>
                            <div className="col-lg-3">
                              <div className="mb-3">
                                <label className="form-label">Time From</label>
                                <CommonSelect
                                  className="select"
                                  options={Time}
                                />
                              </div>
                            </div>
                            <div className="col-lg-3">
                              <div className="d-flex align-items-end">
                                <div className="mb-3 flex-fill">
                                  <label className="form-label">Time To</label>
                                  <CommonSelect
                                    className="select"
                                    options={Time}
                                  />
                                </div>
                                {thursdayContents.length > 1 && (
                                <div className="mb-3 ms-2">
                                  <Link to="#" className="delete-time-table" onClick={() => removethursdayContents(index)}>
                                    <i className="ti ti-trash" />
                                  </Link>
                                </div>
                                )}
                              </div>
                            </div>
                          </div>
                        </div>
                         ))}
                        <div>
                          <Link
                            to="#"
                            className="btn btn-primary add-new-timetable" onClick={addthursdayContents}
                          >
                            <i className="ti ti-square-rounded-plus-filled me-2" />
                            Add New
                          </Link>
                        </div>
                      </div>
                      <div
                        className="tab-pane fade"
                        id="pills-friday"
                        role="tabpanel"
                        aria-labelledby="pills-friday-tab"
                      >
                        {fridayContents.map((_, index) => (
                        <div className="add-timetable-row">
                          <div className="row timetable-count">
                            <div className="col-lg-3">
                              <div className="mb-3">
                                <label className="form-label">Subject</label>
                                <CommonSelect
                                  className="select"
                                  options={language}
                                />
                              </div>
                            </div>
                            <div className="col-lg-3">
                              <div className="mb-3">
                                <label className="form-label">Teacher</label>
                                <CommonSelect
                                  className="select"
                                  options={teacher}
                                />
                              </div>
                            </div>
                            <div className="col-lg-3">
                              <div className="mb-3">
                                <label className="form-label">Time From</label>
                                <CommonSelect
                                  className="select"
                                  options={Time}
                                />
                              </div>
                            </div>
                            <div className="col-lg-3">
                              <div className="d-flex align-items-end">
                                <div className="mb-3 flex-fill">
                                  <label className="form-label">Time To</label>
                                  <CommonSelect
                                    className="select"
                                    options={Timeto}
                                  />
                                </div>
                                {fridayContents.length > 1 && (
                                <div className="mb-3 ms-2">
                                  <Link to="#" className="delete-time-table" onClick={() => removefridayContents(index)}>
                                    <i className="ti ti-trash" />
                                  </Link>
                                </div>
                                )}
                              </div>
                            </div>
                          </div>
                        </div>
                         ))}
                        <div>
                          <Link
                            to="#"
                            className="btn btn-primary add-new-timetable"  onClick={addfridayContents}
                          >
                            <i className="ti ti-square-rounded-plus-filled me-2" />
                            Add New
                          </Link>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div className="modal-footer">
                  <Link
                    to="#"
                    className="btn btn-light me-2"
                    data-bs-dismiss="modal"
                  >
                    Cancel
                  </Link>
                  <Link
                    to="#"
                    className="btn btn-primary"
                    data-bs-dismiss="modal"
                  >
                    Add Time Table
                  </Link>
                </div>
              </form>
            </div>
          </div>
        </div>
        {/* /Add Class Time Table */}
      </>
    </div>
  );
};

export default ClassTimetable;
