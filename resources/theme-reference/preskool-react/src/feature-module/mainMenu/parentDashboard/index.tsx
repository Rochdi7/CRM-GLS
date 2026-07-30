import  { useState } from "react";
import { Link } from "react-router-dom";
import { all_routes } from "../../router/all_routes";
import ImageWithBasePath from "../../../core/common/imageWithBasePath";
import ReactApexChart from "react-apexcharts";

const ParentDashboard = () => {
  const routes = all_routes;
  const [activeStudent, setActiveStudent] = useState<string>("student-1");
  const [statistic_chart] = useState<any>({
    chart: {
      type: "line",
      height: 345,
    },
    series: [
      {
        name: "Avg. Exam Score",
        data: [0, 32, 40, 50, 60, 52, 50, 44, 40, 60, 75, 70], // Sample data
      },
      {
        name: "Avg. Attendance",
        data: [0, 35, 43, 34, 30, 28, 25, 50, 60, 75, 77, 80], // Sample data
      },
    ],
    xaxis: {
      categories: [
        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "May",
        "Jun",
        "Jul",
        "Aug",
        "Sep",
        "Oct",
        "Nov",
        "Dec",
      ],
    },
    tooltip: {
      y: {
        formatter: function (val: any) {
          return val + "%";
        },
      },
      shared: true,
      intersect: false,
      custom: function ({ series, dataPointIndex, w }: any) {
        return `<div class="apexcharts-tooltip">${w.globals.labels[dataPointIndex]}<br>Exam Score: <span style="color: #1E90FF;">${series[0][dataPointIndex]}%</span><br>Attendance: <span style="color: #00BFFF;">${series[1][dataPointIndex]}%</span></div>`;
      },
    },
    dataLabels: {
      enabled: false,
    },
    grid: {
      yaxis: {
        lines: {
          show: true,
        },
      },
      padding: {
        left: -8,
      },
    },
    yaxis: {
      labels: {
        offsetX: -15,
      },
    },
    markers: {
      size: 0,
      colors: ["#1E90FF", "#00BFFF"],
      strokeColors: "#fff",
      strokeWidth: 1,
      hover: {
        size: 7,
      },
    },
    colors: ["#3D5EE1", "#6FCCD8"], // Color for the lines
    legend: {
      position: "top",
      horizontalAlign: "left",
    },
  });
 
  return (
    <>
      {/* Page Wrapper */}
      <div className="page-wrapper">
        <div className="content">
          {/* Page Header */}
          <div className="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div className="my-auto mb-2">
              <h3 className="page-title mb-1">Parent Dashboard</h3>
              <nav>
                <ol className="breadcrumb mb-0">
                  <li className="breadcrumb-item">
                    <Link to={routes.adminDashboard}>Dashboard</Link>
                  </li>
                  <li className="breadcrumb-item active" aria-current="page">
                    Parent Dashboard
                  </li>
                </ol>
              </nav>
            </div>
            <div className="dash-select-student d-flex align-items-center mb-2">
              <h6 className="mb-0">Select Student</h6>
              <div className="student-active d-flex align-items-center ms-2">
                <Link
                  to="#"
                  onClick={() => setActiveStudent("student-1")}
                  className={`avatar avatar-lg p-1 me-2 ${
                    activeStudent === "student-1" && "active"
                  }`}
                >
                  <ImageWithBasePath
                    src="assets/img/students/student-01.jpg"
                    alt="Profile"
                  />
                </Link>
                <Link
                  to="#"
                  onClick={() => setActiveStudent("student-2")}
                  className={`avatar avatar-lg p-1  ${
                    activeStudent === "student-2" && "active"
                  }`}
                >
                  <ImageWithBasePath
                    src="assets/img/students/student-02.jpg"
                    alt="Profile"
                  />
                </Link>
              </div>
            </div>
          </div>
          {/* /Page Header */}
          <div className="row">
            {/* Profile */}
            <div className="col-xxl-5 col-xl-12 d-flex">
              <div className="card bg-dark position-relative flex-fill">
                <div className="card-body">
                  <div className="d-flex align-items-center row-gap-3">
                    <div className="avatar avatar-xxl rounded flex-shrink-0 me-3">
                      <ImageWithBasePath
                        src="assets/img/parents/parent-01.jpg"
                        alt="Img"
                      />
                    </div>
                    <div className="d-block">
                      <span className="badge bg-transparent-primary text-primary mb-1">
                        #P124556
                      </span>
                      <h4 className="text-truncate text-white mb-1">
                        Thomas Bown
                      </h4>
                      <div className="d-flex align-items-center flex-wrap row-gap-2 class-info">
                        <span>Added On : 25 Mar 2024</span>
                        <span>Child : Janet</span>
                      </div>
                    </div>
                  </div>
                  <div className="student-card-bg">
                    <ImageWithBasePath
                      src="assets/img/bg/circle-shape.png"
                      alt="Bg"
                    />
                    <ImageWithBasePath
                      src="assets/img/bg/shape-02.png"
                      alt="Bg"
                    />
                    <ImageWithBasePath
                      src="assets/img/bg/shape-04.png"
                      alt="Bg"
                    />
                    <ImageWithBasePath
                      src="assets/img/bg/blue-polygon.png"
                      alt="Bg"
                    />
                  </div>
                </div>
              </div>
            </div>
            {/* /Profile */}
            {/* Leave */}
            <div className="col-xxl-7 d-flex">
              <div className="row flex-fill">
                <div className="col-xl-4 d-flex flex-column">
                  <div className="d-flex bg-white border rounded flex-wrap justify-content-between align-items-center p-3 row-gap-2 mb-2 animate-card">
                    <div className="d-flex align-items-center">
                      <span className="avatar avatar-sm bg-light-500 me-2 rounded">
                        <i className="ti ti-calendar-event text-dark fs-16" />
                      </span>
                      <h6>Apply Leave</h6>
                    </div>
                    <Link
                      to={routes.studentLeaves}
                      className="badge rounded-circle arrow d-flex align-items-center justify-content-center"
                    >
                      <i className="ti ti-chevron-right fs-14" />
                    </Link>
                  </div>
                  <div className="d-flex bg-white border rounded flex-wrap justify-content-between align-items-center p-3 row-gap-2 mb-4 animate-card">
                    <div className="d-flex align-items-center">
                      <span className="avatar avatar-sm bg-light-500 me-2 rounded">
                        <i className="ti ti-message-up text-dark fs-16" />
                      </span>
                      <h6>Raise a Request</h6>
                    </div>
                    <Link
                      to={routes.approveRequest}
                      className="badge rounded-circle arrow d-flex align-items-center justify-content-center"
                    >
                      <i className="ti ti-chevron-right fs-14" />
                    </Link>
                  </div>
                </div>
                <div className="col-xl-4 col-md-6">
                  <div className="card bg-success-transparent border-3 border-white text-center p-3">
                    <span className="avatar avatar-sm rounded bg-success mx-auto mb-3">
                      <i className="ti ti-calendar-share fs-15" />
                    </span>
                    <h6 className="mb-2">Medical Leaves (10)</h6>
                    <div className="d-flex align-items-center justify-content-between text-default">
                      <p className="border-end mb-0">Used : 05</p>
                      <p>Available : 10</p>
                    </div>
                  </div>
                </div>
                <div className="col-xl-4 col-md-6">
                  <div className="card bg-primary-transparent border-3 border-white text-center p-3">
                    <span className="avatar avatar-sm rounded bg-primary mx-auto mb-3">
                      <i className="ti ti-hexagonal-prism-plus fs-15" />
                    </span>
                    <h6 className="mb-2">Casual Leaves (12)</h6>
                    <div className="d-flex align-items-center justify-content-between text-default">
                      <p className="border-end mb-0">Used : 05</p>
                      <p>Available : 10</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            {/* /Leave */}
          </div>
          <div className="row">
            {/* Events List */}
            <div className="col-xxl-4 col-xl-6 d-flex">
              <div className="card flex-fill">
                <div className="card-header  d-flex align-items-center justify-content-between">
                  <h4 className="card-title">Events List</h4>
                  <Link to={routes.events} className="fw-medium">
                    View All
                  </Link>
                </div>
                <div className="card-body p-0">
                  <ul className="list-group list-group-flush">
                    <li className="list-group-item p-3">
                      <div className="d-flex align-items-center justify-content-between">
                        <div className="d-flex align-items-center">
                          <Link
                            to="#"
                            className="avatar avatar-lg flex-shrink-0 me-2"
                          >
                            <ImageWithBasePath
                              src="assets/img/events/event-01.jpg"
                              className="img-fluid"
                              alt="img"
                            />
                          </Link>
                          <div className="overflow-hidden">
                            <h6 className="mb-1">
                              <Link to={routes.events}>
                                Parents, Teacher Meet
                              </Link>
                            </h6>
                            <p>
                              <i className="ti ti-calendar me-1" />
                              15 July 2024
                            </p>
                          </div>
                        </div>
                        <span className="badge badge-soft-danger d-inline-flex align-items-center">
                          <i className="ti ti-circle-filled fs-5 me-1" />
                          Full Day
                        </span>
                      </div>
                    </li>
                    <li className="list-group-item p-3">
                      <div className="d-flex align-items-center justify-content-between">
                        <div className="d-flex align-items-center">
                          <Link
                            to="#"
                            className="avatar avatar-lg flex-shrink-0 me-2"
                          >
                            <ImageWithBasePath
                              src="assets/img/events/event-02.jpg"
                              className="img-fluid"
                              alt="img"
                            />
                          </Link>
                          <div className="overflow-hidden">
                            <h6 className="mb-1">
                              <Link to={routes.events}>Farewell</Link>
                            </h6>
                            <p>
                              <i className="ti ti-calendar me-1" />
                              11 Mar 2024
                            </p>
                          </div>
                        </div>
                        <span className="badge badge-soft-skyblue d-inline-flex align-items-center">
                          <i className="ti ti-circle-filled fs-5 me-1" />
                          Half Day
                        </span>
                      </div>
                    </li>
                    <li className="list-group-item p-3">
                      <div className="d-flex align-items-center justify-content-between">
                        <div className="d-flex align-items-center">
                          <Link
                            to="#"
                            className="avatar avatar-lg flex-shrink-0 me-2"
                          >
                            <ImageWithBasePath
                              src="assets/img/events/event-03.jpg"
                              className="img-fluid"
                              alt="img"
                            />
                          </Link>
                          <div className="overflow-hidden">
                            <h6 className="mb-1">
                              <Link to={routes.events}>Annual Day</Link>
                            </h6>
                            <p>
                              <i className="ti ti-calendar me-1" />
                              11 Mar 2024
                            </p>
                          </div>
                        </div>
                        <span className="badge badge-soft-skyblue d-inline-flex align-items-center">
                          <i className="ti ti-circle-filled fs-5 me-1" />
                          Half Day
                        </span>
                      </div>
                    </li>
                    <li className="list-group-item p-3">
                      <div className="d-flex align-items-center justify-content-between">
                        <div className="d-flex align-items-center">
                          <Link
                            to="#"
                            className="avatar avatar-lg flex-shrink-0 me-2"
                          >
                            <ImageWithBasePath
                              src="assets/img/events/event-04.jpg"
                              className="img-fluid"
                              alt="img"
                            />
                          </Link>
                          <div className="overflow-hidden">
                            <h6 className="mb-1">
                              <Link to={routes.events}>Holi Celebration</Link>
                            </h6>
                            <p>
                              <i className="ti ti-calendar me-1" />
                              15 July 2024
                            </p>
                          </div>
                        </div>
                        <span className="badge badge-soft-danger d-inline-flex align-items-center">
                          <i className="ti ti-circle-filled fs-5 me-1" />
                          Full Day
                        </span>
                      </div>
                    </li>
                    <li className="list-group-item p-3">
                      <div className="d-flex align-items-center justify-content-between">
                        <div className="d-flex align-items-center">
                          <Link
                            to="#"
                            className="avatar avatar-lg flex-shrink-0 me-2"
                          >
                            <ImageWithBasePath
                              src="assets/img/events/event-05.jpg"
                              className="img-fluid"
                              alt="img"
                            />
                          </Link>
                          <div className="overflow-hidden">
                            <h6 className="mb-1">
                              <Link to={routes.events}>Exam Result</Link>
                            </h6>
                            <p>
                              <i className="ti ti-calendar me-1" />
                              16 July 2024
                            </p>
                          </div>
                        </div>
                        <span className="badge badge-soft-skyblue d-inline-flex align-items-center">
                          <i className="ti ti-circle-filled fs-5 me-1" />
                          Half Day
                        </span>
                      </div>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
            {/* /Events List */}
            {/* Statistics */}
            <div className="col-xxl-8 col-xl-6 d-flex">
              <div className="card flex-fill">
                <div className="card-header d-flex align-items-center justify-content-between">
                  <h4 className="card-title">Statistics</h4>
                  <div className="dropdown">
                    <Link
                      to="#"
                      className="bg-white dropdown-toggle"
                      data-bs-toggle="dropdown"
                    >
                      <i className="ti ti-calendar me-2" />
                      This Month
                    </Link>
                    <ul className="dropdown-menu mt-2 p-3">
                      <li>
                        <Link to="#" className="dropdown-item rounded-1">
                          This Month
                        </Link>
                      </li>
                      <li>
                        <Link to="#" className="dropdown-item rounded-1">
                          This Year
                        </Link>
                      </li>
                      <li>
                        <Link to="#" className="dropdown-item rounded-1">
                          Last Week
                        </Link>
                      </li>
                    </ul>
                  </div>
                </div>
                <div className="card-body pb-0">
                  <div id="statistic_chart" />
                  <ReactApexChart
                    id="statistic_chart"
                    options={statistic_chart}
                    series={statistic_chart.series}
                    type="line"
                    height={345}
                  />
                </div>
              </div>
            </div>
            {/* /Statistics */}
          </div>
          <div className="row">
            {/* Leave Status */}
            <div className="col-xxl-4 col-xl-6 d-flex">
              <div className="card flex-fill">
                <div className="card-header d-flex align-items-center justify-content-between">
                  <h4 className="card-title">Leave Status</h4>
                  <div className="dropdown">
                    <Link
                      to="#"
                      className="bg-white dropdown-toggle"
                      data-bs-toggle="dropdown"
                    >
                      <i className="ti ti-calendar me-2" />
                      This Month
                    </Link>
                    <ul className="dropdown-menu mt-2 p-3">
                      <li>
                        <Link to="#" className="dropdown-item rounded-1">
                          This Month
                        </Link>
                      </li>
                      <li>
                        <Link to="#" className="dropdown-item rounded-1">
                          This Year
                        </Link>
                      </li>
                      <li>
                        <Link to="#" className="dropdown-item rounded-1">
                          Last Week
                        </Link>
                      </li>
                    </ul>
                  </div>
                </div>
                <div className="card-body">
                  <div className="bg-light-300 d-sm-flex align-items-center justify-content-between p-3 mb-3">
                    <div className="d-flex align-items-center mb-2 mb-sm-0">
                      <div className="avatar avatar-lg bg-danger-transparent flex-shrink-0 me-2">
                        <i className="ti ti-brand-socket-io" />
                      </div>
                      <div>
                        <h6 className="mb-1">Emergency Leave</h6>
                        <p>Date : 15 Jun 2024</p>
                      </div>
                    </div>
                    <span className="badge bg-skyblue d-inline-flex align-items-center">
                      <i className="ti ti-circle-filled fs-5 me-1" />
                      Pending
                    </span>
                  </div>
                  <div className="bg-light-300 d-sm-flex align-items-center justify-content-between p-3 mb-3">
                    <div className="d-flex align-items-center mb-2 mb-sm-0">
                      <div className="avatar avatar-lg bg-info-transparent flex-shrink-0 me-2">
                        <i className="ti ti-medical-cross" />
                      </div>
                      <div>
                        <h6 className="mb-1">Medical Leave</h6>
                        <p>Date : 15 Jun 2024</p>
                      </div>
                    </div>
                    <span className="badge bg-success d-inline-flex align-items-center">
                      <i className="ti ti-circle-filled fs-5 me-1" />
                      Approved
                    </span>
                  </div>
                  <div className="bg-light-300 d-sm-flex align-items-center justify-content-between p-3 mb-3">
                    <div className="d-flex align-items-center mb-2 mb-sm-0">
                      <div className="avatar avatar-lg bg-info-transparent flex-shrink-0 me-2">
                        <i className="ti ti-medical-cross" />
                      </div>
                      <div>
                        <h6 className="mb-1">Medical Leave</h6>
                        <p>Date : 16 Jun 2024</p>
                      </div>
                    </div>
                    <span className="badge bg-danger d-inline-flex align-items-center">
                      <i className="ti ti-circle-filled fs-5 me-1" />
                      Declined
                    </span>
                  </div>
                  <div className="bg-light-300 d-sm-flex align-items-center justify-content-between p-3 mb-0">
                    <div className="d-flex align-items-center mb-2 mb-sm-0">
                      <div className="avatar avatar-lg bg-danger-transparent flex-shrink-0 me-2">
                        <i className="ti ti-brand-socket-io" />
                      </div>
                      <div>
                        <h6 className="mb-1">Fever</h6>
                        <p>Date : 16 Jun 2024</p>
                      </div>
                    </div>
                    <span className="badge bg-success d-inline-flex align-items-center">
                      <i className="ti ti-circle-filled fs-5 me-1" />
                      Approved
                    </span>
                  </div>
                </div>
              </div>
            </div>
            {/* /Leave Status */}
            {/* Home Works */}
            <div className="col-xxl-4  col-xl-6 d-flex">
              <div className="card flex-fill">
                <div className="card-header d-flex align-items-center justify-content-between">
                  <h4 className="card-titile">Home Works</h4>
                  <div className="dropdown">
                    <Link
                      to="#"
                      className="bg-white dropdown-toggle"
                      data-bs-toggle="dropdown"
                    >
                      <i className="ti ti-book-2 me-2" />
                      All Subject
                    </Link>
                    <ul className="dropdown-menu mt-2 p-3">
                      <li>
                        <Link to="#" className="dropdown-item rounded-1">
                          Physics
                        </Link>
                      </li>
                      <li>
                        <Link to="#" className="dropdown-item rounded-1">
                          Chemistry
                        </Link>
                      </li>
                      <li>
                        <Link to="#" className="dropdown-item rounded-1">
                          Maths
                        </Link>
                      </li>
                    </ul>
                  </div>
                </div>
                <div className="card-body py-1">
                  <ul className="list-group list-group-flush">
                    <li className="list-group-item py-3 px-0">
                      <div className="d-flex align-items-center">
                        <Link
                          to="#"
                          className="avatar avatar-xl flex-shrink-0 me-2"
                        >
                          <ImageWithBasePath
                            src="assets/img/home-work/home-work-01.jpg"
                            alt="img"
                          />
                        </Link>
                        <div className="overflow-hidden">
                          <p className="d-flex align-items-center text-info mb-1">
                            <i className="ti ti-tag me-2" />
                            Physics
                          </p>
                          <h6 className="text-truncate mb-1">
                            <Link to={routes.classHomeWork}>
                              Write about Theory of Pendulum
                            </Link>
                          </h6>
                          <div className="d-flex align-items-center flex-wrap">
                            <div className="d-flex align-items-center border-end me-1 pe-1">
                              <Link
                                to={routes.teacherDetails}
                                className="avatar avatar-xs flex-shrink-0 me-2"
                              >
                                <ImageWithBasePath
                                  src="assets/img/teachers/teacher-01.jpg"
                                  className="rounded-circle"
                                  alt="teacher"
                                />
                              </Link>
                              <p className="text-dark">Aaron</p>
                            </div>
                            <p>Due by : 16 Jun 2024</p>
                          </div>
                        </div>
                      </div>
                    </li>
                    <li className="list-group-item py-3 px-0">
                      <div className="d-flex align-items-center">
                        <Link
                          to="#"
                          className="avatar avatar-xl flex-shrink-0 me-2"
                        >
                          <ImageWithBasePath
                            src="assets/img/home-work/home-work-02.jpg"
                            alt="img"
                          />
                        </Link>
                        <div className="overflow-hidden">
                          <p className="d-flex align-items-center text-success mb-1">
                            <i className="ti ti-tag me-2" />
                            Chemistry
                          </p>
                          <h6 className="text-truncate mb-1">
                            <Link to={routes.classHomeWork}>
                              Chemistry - Change of Elements
                            </Link>
                          </h6>
                          <div className="d-flex align-items-center flex-wrap">
                            <div className="d-flex align-items-center border-end me-1 pe-1">
                              <Link
                                to={routes.teacherDetails}
                                className="avatar avatar-xs flex-shrink-0 me-2"
                              >
                                <ImageWithBasePath
                                  src="assets/img/teachers/teacher-01.jpg"
                                  className="rounded-circle"
                                  alt="teacher"
                                />
                              </Link>
                              <p className="text-dark">Hellana</p>
                            </div>
                            <p>Due by : 16 Jun 2024</p>
                          </div>
                        </div>
                      </div>
                    </li>
                    <li className="list-group-item py-3 px-0">
                      <div className="d-flex align-items-center">
                        <Link
                          to="#"
                          className="avatar avatar-xl flex-shrink-0 me-2"
                        >
                          <ImageWithBasePath
                            src="assets/img/home-work/home-work-03.jpg"
                            alt="img"
                          />
                        </Link>
                        <div className="overflow-hidden">
                          <p className="d-flex align-items-center text-danger mb-1">
                            <i className="ti ti-tag me-2" />
                            Maths
                          </p>
                          <h6 className="text-truncate mb-1">
                            <Link to={routes.classHomeWork}>
                              Maths - Problems to Solve Page 21
                            </Link>
                          </h6>
                          <div className="d-flex align-items-center flex-wrap">
                            <div className="d-flex align-items-center border-end me-1 pe-1">
                              <Link
                                to={routes.teacherDetails}
                                className="avatar avatar-xs flex-shrink-0 me-2"
                              >
                                <ImageWithBasePath
                                  src="assets/img/teachers/teacher-01.jpg"
                                  className="rounded-circle"
                                  alt="teacher"
                                />
                              </Link>
                              <p className="text-dark">Morgan</p>
                            </div>
                            <p>Due by : 21 Jun 2024</p>
                          </div>
                        </div>
                      </div>
                    </li>
                    <li className="list-group-item py-3 px-0">
                      <div className="d-flex align-items-center">
                        <Link
                          to="#"
                          className="avatar avatar-xl flex-shrink-0 me-2"
                        >
                          <ImageWithBasePath
                            src="assets/img/home-work/home-work-04.jpg"
                            alt="img"
                          />
                        </Link>
                        <div className="overflow-hidden">
                          <p className="d-flex align-items-center text-skyblue mb-1">
                            <i className="ti ti-tag me-2" />
                            Engish
                          </p>
                          <h6 className="text-truncate mb-1">
                            <Link to={routes.classHomeWork}>
                              English - Vocabulary Introduction
                            </Link>
                          </h6>
                          <div className="d-flex align-items-center flex-wrap">
                            <div className="d-flex align-items-center border-end me-1 pe-1">
                              <Link
                                to={routes.teacherDetails}
                                className="avatar avatar-xs flex-shrink-0 me-2"
                              >
                                <ImageWithBasePath
                                  src="assets/img/teachers/teacher-01.jpg"
                                  className="rounded-circle"
                                  alt="teacher"
                                />
                              </Link>
                              <p className="text-dark">Daniel Josua</p>
                            </div>
                            <p>Due by : 21 Jun 2024</p>
                          </div>
                        </div>
                      </div>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
            {/* /Home Works */}
            {/* Fees Reminder */}
            <div className="col-xxl-4 col-xl-12 d-flex">
              <div className="card flex-fill">
                <div className="card-header d-flex align-items-center justify-content-between">
                  <h4 className="card-titile">Fees Reminder</h4>
                  <Link
                    to={routes.feesAssign}
                    className="link-primary fw-medium"
                  >
                    View All
                  </Link>
                </div>
                <div className="card-body py-1">
                  <div className="d-flex align-items-center justify-content-between py-3">
                    <div className="d-flex align-items-center overflow-hidden me-2">
                      <span className="bg-info-transparent avatar avatar-lg me-2 rounded-circle flex-shrink-0">
                        <i className="ti ti-bus-stop fs-16" />
                      </span>
                      <div className="overflow-hidden">
                        <h6 className="text-truncate mb-1">Transport Fees</h6>
                        <p>$2500</p>
                      </div>
                    </div>
                    <div className="text-end">
                      <h6 className="mb-1">Last Date</h6>
                      <p>25 May 2024</p>
                    </div>
                  </div>
                  <div className="d-flex align-items-center justify-content-between py-3">
                    <div className="d-flex align-items-center overflow-hidden me-2">
                      <span className="bg-success-transparent avatar avatar-lg me-2 rounded-circle flex-shrink-0">
                        <i className="ti ti-books fs-16" />
                      </span>
                      <div className="overflow-hidden">
                        <h6 className="text-truncate mb-1">Book Fees</h6>
                        <p>$2500</p>
                      </div>
                    </div>
                    <div className="text-end">
                      <h6 className="mb-1">Last Date</h6>
                      <p>25 May 2024</p>
                    </div>
                  </div>
                  <div className="d-flex align-items-center justify-content-between py-3">
                    <div className="d-flex align-items-center overflow-hidden me-2">
                      <span className="bg-info-transparent avatar avatar-lg me-2 rounded-circle flex-shrink-0">
                        <i className="ti ti-report-money fs-16" />
                      </span>
                      <div className="overflow-hidden">
                        <h6 className="text-truncate mb-1">Exam Fees</h6>
                        <p>$2500</p>
                      </div>
                    </div>
                    <div className="text-end">
                      <h6 className="mb-1">Last Date</h6>
                      <p>25 May 2024</p>
                    </div>
                  </div>
                  <div className="d-flex align-items-center justify-content-between py-3">
                    <div className="d-flex align-items-center overflow-hidden me-2">
                      <span className="bg-skyblue-transparent avatar avatar-lg me-2 rounded-circle flex-shrink-0">
                        <i className="ti ti-meat fs-16" />
                      </span>
                      <div className="overflow-hidden">
                        <h6 className="text-truncate mb-1">
                          Mess Fees{" "}
                          <span className="d-inline-flex align-items-center badge badge-soft-danger">
                            <i className="ti ti-circle-filled me-1 fs-5" />
                            Due
                          </span>
                        </h6>
                        <p className="text-danger">$2500 + $150</p>
                      </div>
                    </div>
                    <div className="text-end">
                      <Link to="#" className="btn btn-primary btn-sm">
                        Pay now
                      </Link>
                    </div>
                  </div>
                  <div className="d-flex align-items-center justify-content-between py-3">
                    <div className="d-flex align-items-center overflow-hidden me-2">
                      <span className="bg-danger-transparent avatar avatar-lg me-2 rounded-circle flex-shrink-0">
                        <i className="ti ti-report-money fs-16" />
                      </span>
                      <div className="overflow-hidden">
                        <h6 className="text-truncate mb-1">Hostel</h6>
                        <p>$2500</p>
                      </div>
                    </div>
                    <div className="text-end">
                      <h6 className="mb-1">Last Date</h6>
                      <p>25 May 2024</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            {/* Fees Reminder */}
          </div>
          <div className="row">
            {/* Exam Result */}
            <div className="col-xxl-8 col-xl-7 d-flex">
              <div className="card flex-fill">
                <div className="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                  <h4 className="card-title mb-3">Exam Result</h4>
                  <div className="d-flex align-items-center">
                    <div className="dropdown me-3 mb-3">
                      <Link
                        to="#"
                        className="bg-white dropdown-toggle"
                        data-bs-toggle="dropdown"
                      >
                        <i className="ti ti-calendar me-2" />
                        All Classes
                      </Link>
                      <ul className="dropdown-menu mt-2 p-3">
                        <li>
                          <Link to="#" className="dropdown-item rounded-1">
                            I
                          </Link>
                        </li>
                        <li>
                          <Link to="#" className="dropdown-item rounded-1">
                            II
                          </Link>
                        </li>
                        <li>
                          <Link to="#" className="dropdown-item rounded-1">
                            III
                          </Link>
                        </li>
                      </ul>
                    </div>
                    <div className="dropdown mb-3">
                      <Link
                        to="#"
                        className="bg-white dropdown-toggle"
                        data-bs-toggle="dropdown"
                      >
                        <i className="ti ti-calendar me-2" />
                        All Exams
                      </Link>
                      <ul className="dropdown-menu mt-2 p-3">
                        <li>
                          <Link to="#" className="dropdown-item rounded-1">
                            Quartely
                          </Link>
                        </li>
                        <li>
                          <Link to="#" className="dropdown-item rounded-1">
                            Practical
                          </Link>
                        </li>
                        <li>
                          <Link to="#" className="dropdown-item rounded-1">
                            1st Term
                          </Link>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
                <div className="card-body px-0">
                  <div className="custom-datatable-filter table-responsive">
                    <table className="table">
                      <thead className="thead-light">
                        <tr>
                          <th>ID</th>
                          <th>Name</th>
                          <th>Class </th>
                          <th>Section</th>
                          <th>Marks %</th>
                          <th>Exams</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td>35013</td>
                          <td>
                            <div className="d-flex align-items-center">
                              <Link
                                to={routes.studentDetail}
                                className="avatar avatar-md"
                              >
                                <ImageWithBasePath
                                  src="assets/img/students/student-01.jpg"
                                  className="img-fluid rounded-circle"
                                  alt="img"
                                />
                              </Link>
                              <div className="ms-2">
                                <p className="text-dark mb-0">
                                  <Link to={routes.studentDetail}>Janet</Link>
                                </p>
                              </div>
                            </div>
                          </td>
                          <td>III</td>
                          <td>A</td>
                          <td>89%</td>
                          <td>Quartely</td>
                          <td>
                            <span className="badge bg-success">Pass</span>
                          </td>
                        </tr>
                        <tr>
                          <td>35013</td>
                          <td>
                            <div className="d-flex align-items-center">
                              <Link
                                to={routes.studentDetail}
                                className="avatar avatar-md"
                              >
                                <ImageWithBasePath
                                  src="assets/img/students/student-02.jpg"
                                  className="img-fluid rounded-circle"
                                  alt="img"
                                />
                              </Link>
                              <div className="ms-2">
                                <p className="text-dark mb-0">
                                  <Link to={routes.studentDetail}>Joann</Link>
                                </p>
                              </div>
                            </div>
                          </td>
                          <td>IV</td>
                          <td>B</td>
                          <td>88%</td>
                          <td>Practical</td>
                          <td>
                            <span className="badge bg-success">Pass</span>
                          </td>
                        </tr>
                        <tr>
                          <td>35010</td>
                          <td>
                            <div className="d-flex align-items-center">
                              <Link
                                to={routes.studentDetail}
                                className="avatar avatar-md"
                              >
                                <ImageWithBasePath
                                  src="assets/img/students/student-04.jpg"
                                  className="img-fluid rounded-circle"
                                  alt="img"
                                />
                              </Link>
                              <div className="ms-2">
                                <p className="text-dark mb-0">
                                  <Link to={routes.studentDetail}>Gifford</Link>
                                </p>
                              </div>
                            </div>
                          </td>
                          <td>I</td>
                          <td>B</td>
                          <td>21%</td>
                          <td>Mid Term</td>
                          <td>
                            <span className="badge bg-success">Pass</span>
                          </td>
                        </tr>
                        <tr>
                          <td>35009</td>
                          <td>
                            <div className="d-flex align-items-center">
                              <Link
                                to={routes.studentDetail}
                                className="avatar avatar-md"
                              >
                                <ImageWithBasePath
                                  src="assets/img/students/student-05.jpg"
                                  className="img-fluid rounded-circle"
                                  alt="img"
                                />
                              </Link>
                              <div className="ms-2">
                                <p className="text-dark mb-0">
                                  <Link to={routes.studentDetail}>Lisa</Link>
                                </p>
                              </div>
                            </div>
                          </td>
                          <td>II</td>
                          <td>B</td>
                          <td>31%</td>
                          <td>Annual</td>
                          <td>
                            <span className="badge bg-danger">Fail</span>
                          </td>
                        </tr>
                        <tr>
                          <td>35015</td>
                          <td>
                            <div className="d-flex align-items-center">
                              <Link
                                to={routes.studentDetail}
                                className="avatar avatar-md"
                              >
                                <ImageWithBasePath
                                  src="assets/img/students/student-08.jpg"
                                  className="img-fluid rounded-circle"
                                  alt="img"
                                />
                              </Link>
                              <div className="ms-2">
                                <p className="text-dark mb-0">
                                  <Link to={routes.studentDetail}>Riana</Link>
                                </p>
                              </div>
                            </div>
                          </td>
                          <td>III</td>
                          <td>A</td>
                          <td>89%</td>
                          <td>Quartely</td>
                          <td>
                            <span className="badge bg-success">Pass</span>
                          </td>
                        </tr>
                        <tr>
                          <td>35013</td>
                          <td>
                            <div className="d-flex align-items-center">
                              <Link
                                to={routes.studentDetail}
                                className="avatar avatar-md"
                              >
                                <ImageWithBasePath
                                  src="assets/img/students/student-06.jpg"
                                  className="img-fluid rounded-circle"
                                  alt="img"
                                />
                              </Link>
                              <div className="ms-2">
                                <p className="text-dark mb-0">
                                  <Link to={routes.studentDetail}>Angelo</Link>
                                </p>
                              </div>
                            </div>
                          </td>
                          <td>IV</td>
                          <td>B</td>
                          <td>88%</td>
                          <td>Practical</td>
                          <td>
                            <span className="badge bg-danger">Fail</span>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
            {/* /Exam Result */}
            {/* Notice Board */}
            <div className="col-xxl-4 col-xl-5 d-flex">
              <div className="card flex-fill">
                <div className="card-header  d-flex align-items-center justify-content-between">
                  <h4 className="card-title">Notice Board</h4>
                  <Link to={routes.noticeBoard} className="fw-medium">
                    View All
                  </Link>
                </div>
                <div className="card-body">
                  <div className="notice-widget">
                    <div className="d-flex align-items-center justify-content-between mb-4">
                      <div className="d-flex align-items-center overflow-hidden me-2">
                        <span className="bg-primary-transparent avatar avatar-md me-2 rounded-circle flex-shrink-0">
                          <i className="ti ti-books fs-16" />
                        </span>
                        <div className="overflow-hidden">
                          <h6 className="text-truncate mb-1">
                            New Syllabus Instructions
                          </h6>
                          <p>
                            <i className="ti ti-calendar me-2" />
                            Added on : 11 Mar 2024
                          </p>
                        </div>
                      </div>
                      <Link to={routes.noticeBoard}>
                        <i className="ti ti-chevron-right fs-16" />
                      </Link>
                    </div>
                    <div className="d-flex align-items-center justify-content-between mb-4">
                      <div className="d-flex align-items-center overflow-hidden me-2">
                        <span className="bg-success-transparent avatar avatar-md me-2 rounded-circle flex-shrink-0">
                          <i className="ti ti-note fs-16" />
                        </span>
                        <div className="overflow-hidden">
                          <h6 className="text-truncate mb-1">
                            World Environment Day Program.....!!!
                          </h6>
                          <p>
                            <i className="ti ti-calendar me-2" />
                            Added on : 21 Apr 2024
                          </p>
                        </div>
                      </div>
                      <Link to={routes.noticeBoard}>
                        <i className="ti ti-chevron-right fs-16" />
                      </Link>
                    </div>
                    <div className="d-flex align-items-center justify-content-between mb-4">
                      <div className="d-flex align-items-center overflow-hidden me-2">
                        <span className="bg-danger-transparent avatar avatar-md me-2 rounded-circle flex-shrink-0">
                          <i className="ti ti-bell-check fs-16" />
                        </span>
                        <div className="overflow-hidden">
                          <h6 className="text-truncate mb-1">
                            Exam Preparation Notification!
                          </h6>
                          <p>
                            <i className="ti ti-calendar me-2" />
                            Added on : 13 Mar 2024
                          </p>
                        </div>
                      </div>
                      <Link to={routes.noticeBoard}>
                        <i className="ti ti-chevron-right fs-16" />
                      </Link>
                    </div>
                    <div className="d-flex align-items-center justify-content-between mb-4">
                      <div className="d-flex align-items-center overflow-hidden me-2">
                        <span className="bg-skyblue-transparent avatar avatar-md me-2 rounded-circle flex-shrink-0">
                          <i className="ti ti-notes fs-16" />
                        </span>
                        <div className="overflow-hidden">
                          <h6 className="text-truncate mb-1">
                            Online Classes Preparation
                          </h6>
                          <p>
                            <i className="ti ti-calendar me-2" />
                            Added on : 24 May 2024
                          </p>
                        </div>
                      </div>
                      <Link to={routes.noticeBoard}>
                        <i className="ti ti-chevron-right fs-16" />
                      </Link>
                    </div>
                    <div className="d-flex align-items-center justify-content-between mb-4">
                      <div className="d-flex align-items-center overflow-hidden me-2">
                        <span className="bg-warning-transparent avatar avatar-md me-2 rounded-circle flex-shrink-0">
                          <i className="ti ti-package fs-16" />
                        </span>
                        <div className="overflow-hidden">
                          <h6 className="text-truncate mb-1">
                            Exam Time Table Release
                          </h6>
                          <p>
                            <i className="ti ti-calendar me-2" />
                            Added on : 24 May 2024
                          </p>
                        </div>
                      </div>
                      <Link to={routes.noticeBoard}>
                        <i className="ti ti-chevron-right fs-16" />
                      </Link>
                    </div>
                    <div className="d-flex align-items-center justify-content-between mb-0">
                      <div className="d-flex align-items-center overflow-hidden me-2">
                        <span className="bg-danger-transparent avatar avatar-md me-2 rounded-circle flex-shrink-0">
                          <i className="ti ti-bell-check fs-16" />
                        </span>
                        <div className="overflow-hidden">
                          <h6 className="text-truncate mb-1">
                            English Exam Preparation
                          </h6>
                          <p>
                            <i className="ti ti-calendar me-2" />
                            Added on : 23 Mar 2024
                          </p>
                        </div>
                      </div>
                      <Link to={routes.noticeBoard}>
                        <i className="ti ti-chevron-right fs-16" />
                      </Link>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            {/* /Notice Board */}
          </div>
        </div>
      </div>
      {/* /Page Wrapper */}
    </>
  );
};

export default ParentDashboard;
