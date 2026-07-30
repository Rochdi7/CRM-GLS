import { useState } from "react";
import { Link } from "react-router-dom";
import { all_routes } from "../../router/all_routes";
import ImageWithBasePath from "../../../core/common/imageWithBasePath";
import AdminDashboardModal from "../adminDashboard/adminDashboardModal";
import ReactApexChart from "react-apexcharts";
import { Calendar } from "primereact/calendar";
import type { Nullable } from "primereact/ts-helpers";
import Slider from "react-slick";
import "slick-carousel/slick/slick.css";
import "slick-carousel/slick/slick-theme.css";
import dayjs from "dayjs";
import { DatePicker } from "antd";

const TeacherDashboard = () => {
  const routes = all_routes;
  const [date, setDate] = useState<Nullable<Date>>(null);
  function SampleNextArrow(props: any) {
    const { style, onClick } = props;
    return (
      <div
        className="slick-nav slick-nav-next class-slides"
        style={{ ...style, display: "flex", top: "-72%", left: "22%" }}
        onClick={onClick}
      >
        <i className="fas fa-chevron-right" style={{ fontSize: "12px" }}></i>
      </div>
    );
  }

  function SamplePrevArrow(props: any) {
    const { style, onClick } = props;
    return (
      <div
        className="slick-nav slick-nav-prev class-slides"
        style={{ ...style, display: "flex", top: "-72%", left: "17%" }}
        onClick={onClick}
      >
        <i className="fas fa-chevron-left" style={{ fontSize: "12px" }}></i>
      </div>
    );
  }
  const settings = {
    dots: false,
    autoplay: false,
    slidesToShow: 4,
    margin: 24,
    speed: 500,
    nextArrow: <SampleNextArrow />,
    prevArrow: <SamplePrevArrow />,
    responsive: [
      {
        breakpoint: 1500,
        settings: {
          slidesToShow: 4,
        },
      },
      {
        breakpoint: 1400,
        settings: {
          slidesToShow: 4,
        },
      },
      {
        breakpoint: 992,
        settings: {
          slidesToShow: 4,
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
          slidesToShow: 1,
        },
      },
    ],
  };
  const Syllabus = {
    dots: false,
    autoplay: false,
    arrows: false,
    slidesToShow: 4,
    margin: 24,
    speed: 500,
    responsive: [
      {
        breakpoint: 1500,
        settings: {
          slidesToShow: 4,
        },
      },
      {
        breakpoint: 1400,
        settings: {
          slidesToShow: 4,
        },
      },
      {
        breakpoint: 992,
        settings: {
          slidesToShow: 4,
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
          slidesToShow: 1,
        },
      },
    ],
  };
  const [studentDonutChart] = useState<any>({
    chart: {
      height: 90,
      type: "donut",
      toolbar: {
        show: false,
      },
      sparkline: {
        enabled: true,
      },
    },
    grid: {
      show: false,
      padding: {
        left: 0,
        right: 0,
      },
    },
    plotOptions: {
      bar: {
        horizontal: false,
        columnWidth: "50%",
      },
    },
    dataLabels: {
      enabled: false,
    },

    series: [95, 5],
    labels: ["Completed", "Pending"],
    legend: { show: false },
    colors: ["#1ABE17", "#E82646"],
    responsive: [
      {
        breakpoint: 480,
        options: {
          chart: {
            width: 100,
          },
          legend: {
            position: "bottom",
          },
        },
      },
    ],
  });
  const [attendance_chart] = useState<any>({
    chart: {
      height: 290,
      type: "donut",
      toolbar: {
        show: false,
      },
    },
    plotOptions: {
      bar: {
        horizontal: false,
        columnWidth: "50%",
      },
    },
    dataLabels: {
      enabled: false,
    },

    series: [60, 5, 15, 20],
    labels: ["Present", "Late", "Half Day", "Absent"],
    colors: ["#1ABE17", "#1170E4", "#E9EDF4", "#E82646"],
    responsive: [
      {
        breakpoint: 480,
        options: {
          chart: {
            width: 200,
          },
          legend: {
            position: "left",
          },
        },
      },
    ],
    legend: {
      position: "bottom",
    },
  });
  const today = new Date();
  const year = today.getFullYear();
  const month = String(today.getMonth() + 1).padStart(2, "0"); // Month is zero-based, so we add 1
  const day = String(today.getDate()).padStart(2, "0");
  const formattedDate = `${month}-${day}-${year}`;
  const defaultValue = dayjs(formattedDate);

  return (
    <>
      {/* Page Wrapper */}
      <div className="page-wrapper">
        <div className="content">
          {/* Page Header */}
          <div className="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div className="my-auto mb-2">
              <h3 className="page-title mb-1">Teacher Dashboard</h3>
              <nav>
                <ol className="breadcrumb mb-0">
                  <li className="breadcrumb-item">
                    <Link to={routes.adminDashboard}>Dashboard</Link>
                  </li>
                  <li className="breadcrumb-item">Dashboard</li>
                  <li className="breadcrumb-item active" aria-current="page">
                    Teacher Dashboard
                  </li>
                </ol>
              </nav>
            </div>
          </div>
          {/* /Page Header */}
          {/* Greeting Section */}
          <div className="row">
            <div className="col-md-12 d-flex">
              <div className="card flex-fill bg-info bg-03">
                <div className="card-body">
                  <h1 className="text-white mb-1"> Good Morning Ms.Teena</h1>
                  <p className="text-white mb-3">Have a Good day at work</p>
                  <p className="text-light">
                    Notice : There is a staff meeting at 9AM today, Dont forget
                    to Attend!!!
                  </p>
                </div>
              </div>
            </div>
          </div>
          {/* /Greeting Section */}
          {/* Teacher-Profile */}
          <div className="row">
            <div className="col-xxl-8 col-xl-12">
              <div className="row">
                <div className="col-xxl-7 col-xl-8 d-flex">
                  <div className="card bg-dark position-relative flex-fill">
                    <div className="card-body pb-1">
                      <div className="d-sm-flex align-items-center justify-content-between row-gap-3">
                        <div className="d-flex align-items-center overflow-hidden mb-3">
                          <div className="avatar avatar-xxl rounded flex-shrink-0 border border-2 border-white me-3">
                            <ImageWithBasePath
                              src="assets/img/teachers/teacher-05.jpg"
                              alt="Img"
                            />
                          </div>
                          <div className="overflow-hidden">
                            <span className="badge bg-transparent-primary text-primary mb-1">
                              #T594651
                            </span>
                            <h3 className="text-white mb-1 text-truncate">
                              Henriques Morgan{" "}
                            </h3>
                            <div className="d-flex align-items-center flex-wrap text-light row-gap-2">
                              <span className="me-2">Classes : I-A, V-B</span>
                              <span className="d-flex align-items-center">
                                <i className="ti ti-circle-filled text-warning fs-7 me-1" />
                                Physics
                              </span>
                            </div>
                          </div>
                        </div>
                        <Link
                          to={routes.editTeacher}
                          className="btn btn-primary flex-shrink-0 mb-3"
                        >
                          Edit Profile
                        </Link>
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
                <div className="col-xxl-5 col-xl-4 d-flex">
                  <div className="card flex-fill">
                    <div className="card-body">
                      <div className="row align-items-center justify-content-between">
                        <div className="col-sm-5">
                          <div
                            id="plan_chart"
                            className="mb-3 mb-sm-0 text-center text-sm-start"
                          ></div>
                          <ReactApexChart
                            id="plan_chart"
                            className="mb-3 mb-sm-0 text-center text-sm-start"
                            options={studentDonutChart}
                            series={studentDonutChart.series}
                            type="donut"
                            height={90}
                          />
                        </div>
                        <div className="col-sm-7">
                          <div className=" text-center text-sm-start">
                            <h4 className="mb-3">Syllabus</h4>
                            <p className="mb-2">
                              <i className="ti ti-circle-filled text-success me-1" />
                              Completed :{" "}
                              <span className="fw-semibold">95%</span>
                            </p>
                            <p>
                              <i className="ti ti-circle-filled text-danger me-1" />
                              Pending :<span className="fw-semibold">5%</span>
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              {/* Today's Class */}
              <div className="card">
                <div className="card-header d-flex align-items-center justify-content-between">
                  <div className="d-flex align-items-center">
                    <h4 className="me-2">Today's Class</h4>
                    <div className="owl-nav slide-nav2 text-end nav-control" />
                  </div>
                  <div className="d-inline-flex align-items-center class-datepick">
                    <span className="icon">
                      <i className="ti ti-chevron-left" />
                    </span>
                    {/* <input
                      type="text"
                      className="form-control datetimepicker border-0"
                      placeholder="16 May 2024"
                    /> */}
                    <DatePicker
                      className="form-control datetimepicker border-0"
                      format={{
                        format: "DD-MM-YYYY",
                        type: "mask",
                      }}
                      defaultValue={defaultValue}
                      placeholder="16 May 2024"
                    />
                    <span className="icon">
                      <i className="ti ti-chevron-right" />
                    </span>
                  </div>
                </div>
                <div className="card-body">
                  <Slider
                    {...settings}
                    className="owl-carousel owl-theme task-slider"
                  >
                    <div className="item">
                      <div className="bg-light-400 rounded p-3">
                        <span className="text-decoration-line-through badge badge-danger badge-lg mb-2">
                          <i className="ti ti-clock me-1" />
                          09:00 - 09:45
                        </span>
                        <p className="text-dark">Class V, B</p>
                      </div>
                    </div>
                    <div className="item">
                      <div className="bg-light-400 rounded p-3">
                        <span className="text-decoration-line-through badge badge-danger badge-lg mb-2">
                          <i className="ti ti-clock me-1" />
                          09:00 - 09:45
                        </span>
                        <p className="text-dark">Class IV, C</p>
                      </div>
                    </div>
                    <div className="item">
                      <div className="bg-light-400 rounded p-3">
                        <span className="badge badge-primary badge-lg mb-2">
                          <i className="ti ti-clock me-1" />
                          11:30 - 12:150
                        </span>
                        <p className="text-dark">Class V, B</p>
                      </div>
                    </div>
                    <div className="item">
                      <div className="bg-light-400 rounded p-3">
                        <span className="badge badge-primary badge-lg mb-2">
                          <i className="ti ti-clock me-1" />
                          01:30 - 02:15
                        </span>
                        <p className="text-dark">Class V, B</p>
                      </div>
                    </div>
                    <div className="item">
                      <div className="bg-light-400 rounded p-3">
                        <span className="badge badge-primary badge-lg mb-2">
                          <i className="ti ti-clock me-1" />
                          02:15 - 03:00
                        </span>
                        <p className="text-dark">Class V, B</p>
                      </div>
                    </div>
                  </Slider>
                </div>
              </div>
              {/* /Today's Class */}
              <div className="row">
                {/* Attendance */}
                <div className="col-xxl-6 col-xl-6 col-md-6 d-flex">
                  <div className="card flex-fill">
                    <div className="card-header d-flex align-items-center justify-content-between">
                      <h4 className="card-title">Attendance</h4>
                      <div className="card-dropdown">
                        <Link
                          to="#"
                          className="dropdown-toggle p-2"
                          data-bs-toggle="dropdown"
                        >
                          <i className="ti ti-calendar-due" />
                          This Week
                        </Link>
                        <div className="dropdown-menu  dropdown-menu-end">
                          <ul>
                            <li>
                              <Link to="#">This Week</Link>
                            </li>
                            <li>
                              <Link to="#">Last Week</Link>
                            </li>
                            <li>
                              <Link to="#">Last Month</Link>
                            </li>
                          </ul>
                        </div>
                      </div>
                    </div>
                    <div className="card-body pb-0">
                      <div className="bg-light-300 rounde border p-3 mb-3">
                        <div className="d-flex align-items-center justify-content-between flex-wrap">
                          <h6 className="mb-2">Last 7 Days </h6>
                          <p className="mb-2">14 May 2024 - 21 May 2024</p>
                        </div>
                        <div className="d-flex align-items-center gap-1 flex-wrap">
                          <Link to="#" className="badge badge-lg bg-success">
                            M
                          </Link>
                          <Link to="#" className="badge badge-lg bg-success">
                            T
                          </Link>
                          <Link to="#" className="badge badge-lg bg-success">
                            W
                          </Link>
                          <Link to="#" className="badge badge-lg bg-success">
                            T
                          </Link>
                          <Link to="#" className="badge badge-lg bg-danger">
                            F
                          </Link>
                          <Link
                            to="#"
                            className="badge badge-lg bg-white border text-default"
                          >
                            S
                          </Link>
                          <Link
                            to="#"
                            className="badge badge-lg  bg-white border text-gray-1"
                          >
                            S
                          </Link>
                        </div>
                      </div>
                      <p className="mb-3">
                        <i className="ti ti-calendar-heart text-primary me-2" />
                        No of total working days{" "}
                        <span className="fw-medium text-dark"> 28 Days</span>
                      </p>
                      <div className="border rounded p-3">
                        <div className="row">
                          <div className="col text-center border-end">
                            <p className="mb-1">Present</p>
                            <h5>25</h5>
                          </div>
                          <div className="col text-center border-end">
                            <p className="mb-1">Absent</p>
                            <h5>2</h5>
                          </div>
                          <div className="col text-center border-end">
                            <p className="mb-1">Halfday</p>
                            <h5>0</h5>
                          </div>
                          <div className="col text-center">
                            <p className="mb-1">Late</p>
                            <h5>1</h5>
                          </div>
                        </div>
                      </div>
                      <div className="attendance-chart text-center">
                        {/* <div id="attendance_chart" /> */}
                        <ReactApexChart
                          id="attendance_chart"
                          className="mb-3 mb-sm-0 text-center text-sm-start"
                          options={attendance_chart}
                          series={attendance_chart.series}
                          type="donut"
                          height={250}
                        />
                      </div>
                    </div>
                  </div>
                </div>
                {/* /Attendance */}
                {/* Best Performers */}
                <div className="col-xxl-6 col-xl-6 col-md-6 d-flex flex-column">
                  <div className="card">
                    <div className="card-header d-flex align-items-center justify-content-between">
                      <h4 className="card-title">Best Performers</h4>
                      <Link
                        to={routes.studentList}
                        className="link-primary fw-medium"
                      >
                        View All
                      </Link>
                    </div>
                    <div className="card-body pb-1">
                      <div className="d-sm-flex align-items-center mb-1">
                        <div className="w-50 mb-2">
                          <h6>Class IV, C</h6>
                        </div>
                        <div className="class-progress w-100 ms-sm-3 mb-3">
                          <div
                            className="progress justify-content-between"
                            role="progressbar"
                            aria-label="Basic example"
                            aria-valuenow={0}
                            aria-valuemin={0}
                            aria-valuemax={100}
                          >
                            <div
                              className="progress-bar bg-primary"
                              style={{ width: "80%" }}
                            >
                              <div className="avatar-list-stacked avatar-group-xs d-flex">
                                <span className="avatar avatar-rounded">
                                  <ImageWithBasePath
                                    className="border border-white"
                                    src="assets/img/students/student-01.jpg"
                                    alt="img"
                                  />
                                </span>
                                <span className="avatar avatar-rounded">
                                  <ImageWithBasePath
                                    className="border border-white"
                                    src="assets/img/students/student-02.jpg"
                                    alt="img"
                                  />
                                </span>
                                <span className="avatar avatar-rounded">
                                  <ImageWithBasePath
                                    src="assets/img/students/student-03.jpg"
                                    alt="img"
                                  />
                                </span>
                              </div>
                            </div>
                            <span className="badge">80%</span>
                          </div>
                        </div>
                      </div>
                      <div className="d-sm-flex align-items-center">
                        <div className="w-50 mb-2">
                          <h6>Class III, B</h6>
                        </div>
                        <div className="class-progress w-100 ms-sm-3 mb-3">
                          <div
                            className="progress justify-content-between"
                            role="progressbar"
                            aria-label="Basic example"
                            aria-valuenow={0}
                            aria-valuemin={0}
                            aria-valuemax={100}
                          >
                            <div
                              className="progress-bar bg-warning"
                              style={{ width: "54%" }}
                            >
                              <div className="avatar-list-stacked avatar-group-xs d-flex">
                                <span className="avatar avatar-rounded">
                                  <ImageWithBasePath
                                    className="border border-white"
                                    src="assets/img/profiles/avatar-27.jpg"
                                    alt="img"
                                  />
                                </span>
                                <span className="avatar avatar-rounded">
                                  <ImageWithBasePath
                                    className="border border-white"
                                    src="assets/img/students/student-05.jpg"
                                    alt="img"
                                  />
                                </span>
                                <span className="avatar avatar-rounded">
                                  <ImageWithBasePath
                                    src="assets/img/students/student-06.jpg"
                                    alt="img"
                                  />
                                </span>
                              </div>
                            </div>
                            <span className="badge">54%</span>
                          </div>
                        </div>
                      </div>
                      <div className="d-sm-flex align-items-center mb-1">
                        <div className="w-50 mb-2">
                          <h6>Class V, A</h6>
                        </div>
                        <div className="class-progress w-100 ms-sm-3 mb-3">
                          <div
                            className="progress justify-content-between"
                            role="progressbar"
                            aria-label="Basic example"
                            aria-valuenow={0}
                            aria-valuemin={0}
                            aria-valuemax={100}
                          >
                            <div
                              className="progress-bar bg-skyblue"
                              style={{ width: "76%" }}
                            >
                              <div className="avatar-list-stacked avatar-group-xs d-flex">
                                <span className="avatar avatar-rounded">
                                  <ImageWithBasePath
                                    className="border border-white"
                                    src="assets/img/profiles/avatar-27.jpg"
                                    alt="img"
                                  />
                                </span>
                                <span className="avatar avatar-rounded">
                                  <ImageWithBasePath
                                    className="border border-white"
                                    src="assets/img/students/student-05.jpg"
                                    alt="img"
                                  />
                                </span>
                                <span className="avatar avatar-rounded">
                                  <ImageWithBasePath
                                    src="assets/img/students/student-06.jpg"
                                    alt="img"
                                  />
                                </span>
                              </div>
                            </div>
                            <span className="badge">7%</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div className="card flex-fill">
                    <div className="card-header d-flex align-items-center justify-content-between">
                      <h4 className="card-title">Student Progress</h4>
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
                      <div className="d-flex align-items-center justify-content-between p-3 mb-2 border br-5">
                        <div className="d-flex align-items-center overflow-hidden me-2">
                          <Link
                            to="#"
                            className="avatar avatar-lg flex-shrink-0 br-6 me-2"
                          >
                            <ImageWithBasePath
                              src="assets/img/students/student-09.jpg"
                              alt="student"
                            />
                          </Link>
                          <div className="overflow-hidden">
                            <h6 className="mb-1 text-truncate">
                              <Link to="#">Susan Boswell</Link>
                            </h6>
                            <p>III, B</p>
                          </div>
                        </div>
                        <div className="d-flex align-items-center">
                          <ImageWithBasePath
                            src="assets/img/icons/medal.svg"
                            alt="icon"
                          />
                          <span className="badge badge-success ms-2">98%</span>
                        </div>
                      </div>
                      <div className="d-flex align-items-center justify-content-between p-3 mb-2 border br-5">
                        <div className="d-flex align-items-center overflow-hidden me-2">
                          <Link
                            to="#"
                            className="avatar avatar-lg flex-shrink-0 br-6 me-2"
                          >
                            <ImageWithBasePath
                              src="assets/img/students/student-12.jpg"
                              alt="student"
                            />
                          </Link>
                          <div className="overflow-hidden">
                            <h6 className="mb-1 text-truncate">
                              <Link to="#">Richard Mayes</Link>
                            </h6>
                            <p>V, A</p>
                          </div>
                        </div>
                        <div className="d-flex align-items-center">
                          <ImageWithBasePath
                            src="assets/img/icons/medal-2.svg"
                            alt="icon"
                          />
                          <span className="badge badge-success ms-2">98%</span>
                        </div>
                      </div>
                      <div className="d-flex align-items-center justify-content-between p-3 mb-0 border rounded">
                        <div className="d-flex align-items-center overflow-hidden me-2">
                          <Link
                            to="#"
                            className="avatar avatar-lg flex-shrink-0 br-6 me-2"
                          >
                            <ImageWithBasePath
                              src="assets/img/students/student-11.jpg"
                              alt="student"
                            />
                          </Link>
                          <div className="overflow-hidden">
                            <h6 className="mb-1 text-truncate">
                              <Link to="#">Veronica Randle</Link>
                            </h6>
                            <p>V, B</p>
                          </div>
                        </div>
                        <div className="d-flex align-items-center">
                          <span className="badge bg-info">78%</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                {/* /Best Performers */}
              </div>
            </div>
            {/* Schedules */}
            <div className="col-xxl-4 col-xl-12 d-flex">
              <div className="card flex-fill">
                <div className="card-header d-flex align-items-center justify-content-between">
                  <h4 className="card-title">Schedules</h4>
                  <Link
                    to="#"
                    className="link-primary fw-medium me-2"
                    data-bs-toggle="modal"
                    data-bs-target="#add_event"
                  >
                    <i className="ti ti-square-plus me-1" />
                    Add New
                  </Link>
                </div>
                <div className="card-body">
                  {/* <div className="datepic mb-4" /> */}
                  <Calendar
                    className="datepickers mb-4 custom-cal-react"
                    value={date}
                    onChange={(e) => setDate(e.value)}
                    inline
                  />
                  {/* <DatePicker
                        selected={startDate}
                        onChange={(date:any) => setStartDate(date)}
                        inline
                        /> */}
                  <h4 className="mb-3">Upcoming Events</h4>
                  <div className="event-scroll">
                    {/* Event Item */}
                    <div className="border-start border-danger border-3 shadow-sm p-3 mb-3">
                      <div className="d-flex align-items-center mb-3 pb-3 border-bottom">
                        <span className="avatar p-1 me-2 bg-danger-transparent flex-shrink-0">
                          <i className="ti ti-vacuum-cleaner fs-24" />
                        </span>
                        <div className="flex-fill">
                          <h6 className="mb-1">Vacation Meeting</h6>
                          <p className="d-flex align-items-center">
                            <i className="ti ti-calendar me-1" />
                            07 July 2024 - 07 July 2024
                          </p>
                        </div>
                      </div>
                      <div className="d-flex align-items-center justify-content-between">
                        <p className="mb-0">
                          <i className="ti ti-clock me-1" />
                          09:10 AM - 10:50 PM
                        </p>
                        <div className="avatar-list-stacked avatar-group-sm">
                          <span className="avatar border-0">
                            <ImageWithBasePath
                              src="assets/img/parents/parent-11.jpg"
                              className="rounded-circle"
                              alt="img"
                            />
                          </span>
                          <span className="avatar border-0">
                            <ImageWithBasePath
                              src="assets/img/parents/parent-13.jpg"
                              className="rounded-circle"
                              alt="img"
                            />
                          </span>
                        </div>
                      </div>
                    </div>
                    {/* /Event Item */}
                    {/* Event Item */}
                    <div className="border-start border-skyblue border-3 shadow-sm p-3 mb-3">
                      <div className="d-flex align-items-center mb-3 pb-3 border-bottom">
                        <span className="avatar p-1 me-2 bg-teal-transparent flex-shrink-0">
                          <i className="ti ti-user-edit text-info fs-20" />
                        </span>
                        <div className="flex-fill">
                          <h6 className="mb-1">Parents, Teacher Meet</h6>
                          <p className="d-flex align-items-center">
                            <i className="ti ti-calendar me-1" />
                            15 July 2024
                          </p>
                        </div>
                      </div>
                      <div className="d-flex align-items-center justify-content-between">
                        <p className="mb-0">
                          <i className="ti ti-clock me-1" />
                          09:10AM - 10:50PM
                        </p>
                        <div className="avatar-list-stacked avatar-group-sm">
                          <span className="avatar border-0">
                            <ImageWithBasePath
                              src="assets/img/parents/parent-01.jpg"
                              className="rounded-circle"
                              alt="img"
                            />
                          </span>
                          <span className="avatar border-0">
                            <ImageWithBasePath
                              src="assets/img/parents/parent-07.jpg"
                              className="rounded-circle"
                              alt="img"
                            />
                          </span>
                          <span className="avatar border-0">
                            <ImageWithBasePath
                              src="assets/img/parents/parent-02.jpg"
                              className="rounded-circle"
                              alt="img"
                            />
                          </span>
                        </div>
                      </div>
                    </div>
                    {/* /Event Item */}
                    {/* Event Item */}
                    <div className="border-start border-info border-3 shadow-sm p-3 mb-3">
                      <div className="d-flex align-items-center mb-3 pb-3 border-bottom">
                        <span className="avatar p-1 me-2 bg-info-transparent flex-shrink-0">
                          <i className="ti ti-users-group fs-20" />
                        </span>
                        <div className="flex-fill">
                          <h6 className="mb-1">Staff Meeting</h6>
                          <p className="d-flex align-items-center">
                            <i className="ti ti-calendar me-1" />
                            10 July 2024
                          </p>
                        </div>
                      </div>
                      <div className="d-flex align-items-center justify-content-between">
                        <p className="mb-0">
                          <i className="ti ti-clock me-1" />
                          09:10AM - 10:50PM
                        </p>
                        <div className="avatar-list-stacked avatar-group-sm">
                          <span className="avatar border-0">
                            <ImageWithBasePath
                              src="assets/img/parents/parent-05.jpg"
                              className="rounded-circle"
                              alt="img"
                            />
                          </span>
                          <span className="avatar border-0">
                            <ImageWithBasePath
                              src="assets/img/parents/parent-06.jpg"
                              className="rounded-circle"
                              alt="img"
                            />
                          </span>
                          <span className="avatar border-0">
                            <ImageWithBasePath
                              src="assets/img/parents/parent-07.jpg"
                              className="rounded-circle"
                              alt="img"
                            />
                          </span>
                        </div>
                      </div>
                    </div>
                    {/* /Event Item */}
                    {/* Event Item */}
                    <div className="border-start border-secondary border-3 shadow-sm p-3 mb-0">
                      <div className="d-flex align-items-center mb-3 pb-3 border-bottom">
                        <span className="avatar p-1 me-2 bg-secondary-transparent flex-shrink-0">
                          <i className="ti ti-campfire fs-24" />
                        </span>
                        <div className="flex-fill">
                          <h6 className="mb-1">Admission Camp</h6>
                          <p className="d-flex align-items-center">
                            <i className="ti ti-calendar me-1" />
                            12 July 2024
                          </p>
                        </div>
                      </div>
                      <div className="d-flex align-items-center justify-content-between">
                        <p className="mb-0">
                          <i className="ti ti-clock me-1" />
                          09:10 AM - 10:50 PM
                        </p>
                        <div className="avatar-list-stacked avatar-group-sm">
                          <span className="avatar border-0">
                            <ImageWithBasePath
                              src="assets/img/parents/parent-11.jpg"
                              className="rounded-circle"
                              alt="img"
                            />
                          </span>
                          <span className="avatar border-0">
                            <ImageWithBasePath
                              src="assets/img/parents/parent-13.jpg"
                              className="rounded-circle"
                              alt="img"
                            />
                          </span>
                        </div>
                      </div>
                    </div>
                    {/* /Event Item */}
                  </div>
                </div>
              </div>
            </div>
            {/* /Schedules */}
          </div>
          {/* Teacher-profile */}
          {/* Syllabus */}
          <div className="row">
            <div className="col-md-12">
              <div className="card">
                <div className="card-header d-flex align-items-center justify-content-between">
                  <h4 className="card-title">Syllabus / Lesson Plan</h4>
                  <Link
                    to={routes.classSyllabus}
                    className="link-primary fw-medium"
                  >
                    View All
                  </Link>
                </div>
                <div className="card-body">
                  <Slider
                    {...Syllabus}
                    className="owl-carousel owl-theme lesson"
                  >
                    <div className="item">
                      <div className="card mb-0">
                        <div className="card-body">
                          <div className="bg-success-transparent rounded p-2 fw-semibold mb-3 text-center">
                            Class V, B
                          </div>
                          <div className="border-bottom mb-3">
                            <h5 className="mb-3">
                              Introduction Note to Physics on Tech
                            </h5>
                            <div className="progress progress-xs mb-3">
                              <div
                                className="progress-bar bg-success"
                                role="progressbar"
                                style={{ width: "80%" }}
                                aria-valuenow={25}
                                aria-valuemin={0}
                                aria-valuemax={100}
                              />
                            </div>
                          </div>
                          <div className="d-flex align-items-center justify-content-between">
                            <Link
                              to={routes.sheduleClasses}
                              className="fw-medium"
                            >
                              <i className="ti ti-edit me-1" />
                              Reschedule
                            </Link>
                            <Link to="#" className="link-primary">
                              <i className="ti ti-share-3 me-1" />
                              Share
                            </Link>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="item">
                      <div className="card mb-0">
                        <div className="card-body">
                          <div className="bg-warning-transparent br-5 p-2 fw-semibold mb-3 text-center">
                            Class V, A
                          </div>
                          <div className="border-bottom mb-3">
                            <h5 className="mb-3">
                              Biometric &amp; their Working Functionality
                            </h5>
                            <div className="progress progress-xs mb-3">
                              <div
                                className="progress-bar bg-warning"
                                role="progressbar"
                                style={{ width: "80%" }}
                                aria-valuenow={25}
                                aria-valuemin={0}
                                aria-valuemax={100}
                              />
                            </div>
                          </div>
                          <div className="d-flex align-items-center justify-content-between">
                            <Link
                              to={routes.sheduleClasses}
                              className="fw-medium"
                            >
                              <i className="ti ti-edit me-1" />
                              Reschedule
                            </Link>
                            <Link to="#" className="link-primary">
                              <i className="ti ti-share-3 me-1" />
                              Share
                            </Link>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="item">
                      <div className="card mb-0">
                        <div className="card-body">
                          <div className="bg-info-transparent br-5 p-2 fw-semibold mb-3 text-center">
                            Class IV, C
                          </div>
                          <div className="border-bottom mb-3">
                            <h5 className="mb-3">
                              Analyze and interpret literary texts skills
                            </h5>
                            <div className="progress progress-xs mb-3">
                              <div
                                className="progress-bar bg-info"
                                role="progressbar"
                                style={{ width: "80%" }}
                                aria-valuenow={25}
                                aria-valuemin={0}
                                aria-valuemax={100}
                              />
                            </div>
                          </div>
                          <div className="d-flex align-items-center justify-content-between">
                            <Link
                              to={routes.sheduleClasses}
                              className="fw-medium"
                            >
                              <i className="ti ti-edit me-1" />
                              Reschedule
                            </Link>
                            <Link to="#" className="link-primary">
                              <i className="ti ti-share-3 me-1" />
                              Share
                            </Link>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="item">
                      <div className="card mb-0">
                        <div className="card-body">
                          <div className="bg-danger-transparent br-5 p-2 fw-semibold mb-3 text-center">
                            Class V, A
                          </div>
                          <div className="border-bottom mb-3">
                            <h5 className="mb-3">
                              Enhance vocabulary and grammar skills
                            </h5>
                            <div className="progress progress-xs mb-3">
                              <div
                                className="progress-bar bg-danger"
                                role="progressbar"
                                style={{ width: "30%" }}
                                aria-valuenow={25}
                                aria-valuemin={0}
                                aria-valuemax={100}
                              />
                            </div>
                          </div>
                          <div className="d-flex align-items-center justify-content-between">
                            <Link
                              to={routes.sheduleClasses}
                              className="fw-medium"
                            >
                              <i className="ti ti-edit me-1" />
                              Reschedule
                            </Link>
                            <Link to="#" className="link-primary">
                              <i className="ti ti-share-3 me-1" />
                              Share
                            </Link>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="item">
                      <div className="card mb-0">
                        <div className="card-body">
                          <div className="bg-secondary-transparent br-5 p-2 fw-semibold mb-3 text-center">
                            Class VII, A
                          </div>
                          <div className="border-bottom mb-3">
                            <h5 className="mb-3">
                              Atomic structure and periodic table skills
                            </h5>
                            <div className="progress progress-xs mb-3">
                              <div
                                className="progress-bar bg-secondary"
                                role="progressbar"
                                style={{ width: "70%" }}
                                aria-valuenow={25}
                                aria-valuemin={0}
                                aria-valuemax={100}
                              />
                            </div>
                          </div>
                          <div className="d-flex align-items-center justify-content-between">
                            <Link
                              to={routes.sheduleClasses}
                              className="fw-medium"
                            >
                              <i className="ti ti-edit me-1" />
                              Reschedule
                            </Link>
                            <Link to="#" className="link-primary">
                              <i className="ti ti-share-3 me-1" />
                              Share
                            </Link>
                          </div>
                        </div>
                      </div>
                    </div>
                  </Slider>
                </div>
              </div>
            </div>
          </div>
          {/* /Syllabus */}
          <div className="row">
            {/* Student Marks */}
            <div className="col-xxl-8 col-xl-7 d-flex">
              <div className="card flex-fill">
                <div className="card-header d-flex align-items-center justify-content-between flex-wrap">
                  <h4 className="card-title ">Student Marks</h4>
                  <div className="d-flex align-items-center">
                    <div className="dropdown me-2 ">
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
                    <div className="dropdown ">
                      <Link
                        to="#"
                        className="bg-white dropdown-toggle"
                        data-bs-toggle="dropdown"
                      >
                        <i className="ti ti-calendar me-2" />
                        All Sections
                      </Link>
                      <ul className="dropdown-menu mt-2 p-3">
                        <li>
                          <Link to="#" className="dropdown-item rounded-1">
                            A
                          </Link>
                        </li>
                        <li>
                          <Link to="#" className="dropdown-item rounded-1">
                            B
                          </Link>
                        </li>
                        <li>
                          <Link to="#" className="dropdown-item rounded-1">
                            C
                          </Link>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
                <div className="card-body px-0">
                  <div className="custom-datatable-filter table-responsive">
                    <table className="table ">
                      <thead className="thead-light">
                        <tr>
                          <th>ID</th>
                          <th>Name</th>
                          <th>Class </th>
                          <th>Section</th>
                          <th>Marks %</th>
                          <th>CGPA</th>
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
                          <td>4.2</td>
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
                          <td>3.2</td>
                          <td>
                            <span className="badge bg-success">Pass</span>
                          </td>
                        </tr>
                        <tr>
                          <td>35011</td>
                          <td>
                            <div className="d-flex align-items-center">
                              <Link
                                to={routes.studentDetail}
                                className="avatar avatar-md"
                              >
                                <ImageWithBasePath
                                  src="assets/img/students/student-03.jpg"
                                  className="img-fluid rounded-circle"
                                  alt="img"
                                />
                              </Link>
                              <div className="ms-2">
                                <p className="text-dark mb-0">
                                  <Link to={routes.studentDetail}>
                                    Kathleen
                                  </Link>
                                </p>
                              </div>
                            </div>
                          </td>
                          <td>II</td>
                          <td>A</td>
                          <td>69%</td>
                          <td>4.5</td>
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
                          <td>4.5</td>
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
                          <td>3.9</td>
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
            {/* /Student Marks */}
            {/* Leave Status */}
            <div className="col-xxl-4 col-xl-5 d-flex">
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
                  <div className="bg-light-300 d-sm-flex align-items-center justify-content-between p-3">
                    <div className="d-flex align-items-center mb-2 mb-sm-0">
                      <div className="avatar avatar-lg bg-danger-transparent flex-shrink-0 me-2">
                        <i className="ti ti-brand-socket-io"></i>
                      </div>
                      <div>
                        <h6 className="mb-1">Not Well</h6>
                        <p>Date : 16 Jun 2024</p>
                      </div>
                    </div>
                    <span className="badge bg-success d-inline-flex align-items-center">
                      <i className="ti ti-circle-filled fs-5 me-1"></i>Approved
                    </span>
                  </div>
                </div>
              </div>
            </div>
            {/* /Leave Status */}
          </div>
        </div>
      </div>
      <AdminDashboardModal />
      {/* /Page Wrapper */}
    </>
  );
};

export default TeacherDashboard;
