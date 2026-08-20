/* eslint-disable */
import { useState, useEffect } from "react";
import FullCalendar from "@fullcalendar/react";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import interactionPlugin from "@fullcalendar/interaction";

import Select from "react-select";
import { Link } from "react-router-dom";
import { all_routes } from "../../router/all_routes";

const Calendar = () => {
  const [, setcalenderevent] = useState(""),
    [, setiseditdelete] = useState(false),
    [, setaddneweventobj] = useState(null),
    [, setisnewevent] = useState(false),
    [, setevent_title] = useState(""),
    [weekendsVisible] = useState(true),
    defaultEvents = [
      {
        title: "Event Name 4",
        start: Date.now() + 148000000,
        className: "bg-purple",
      },
      {
        title: "Test Event 1",
        start: Date.now(),
        end: Date.now(),
        className: "bg-success",
      },
      {
        title: "Test Event 2",
        start: Date.now() + 168000000,
        className: "bg-info",
      },
      {
        title: "Test Event 3",
        start: Date.now() + 338000000,
        className: "bg-primary",
      },
    ];
  useEffect(() => {
    let elements = Array.from(
      document.getElementsByClassName("react-datepicker-wrapper")
    );
    elements.map((element) => element.classList.add("width-100"));
  }, []);

  const handleEventClick = (clickInfo: any) => {
    setiseditdelete(false);
    setevent_title(clickInfo.event.title);
    setcalenderevent(clickInfo.event);
  };

  const handleDateSelect = (selectInfo: any) => {
    setisnewevent(true);
    setaddneweventobj(selectInfo);
  };

  const routes = all_routes;
  const options1 = [
    { value: "Success", label: "Success" },
    { value: "Danger", label: "Danger" },
    { value: "Info", label: "Info" },
    { value: "Primary", label: "Primary" },
    { value: "Warning", label: "Warning" },
    { value: "Inverse", label: "Inverse" },
  ];

  const defaultValue = options1[0];

  return (
    <>
      <div className="page-wrapper">
        <div className="content">
          <>
            {/* Page Header */}
            <div className="d-md-flex d-block align-items-center justify-content-between mb-3">
              <div className="my-auto mb-2">
                <h3 className="page-title mb-1">Calendar</h3>
                <nav>
                  <ol className="breadcrumb mb-0">
                    <li className="breadcrumb-item">
                      <Link to={routes.adminDashboard}>Dashboard</Link>
                    </li>
                    <li className="breadcrumb-item">
                      <Link to="#">Application</Link>
                    </li>
                    <li className="breadcrumb-item active" aria-current="page">
                      Calendar
                    </li>
                  </ol>
                </nav>
              </div>
              <div className="d-flex my-xl-auto right-content align-items-center flex-wrap">
                <div className="mb-2">
                  <Link
                    to="#"
                    className="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#add_event"
                  >
                    Create Event
                  </Link>
                </div>
              </div>
            </div>
            {/* /Page Header */}
          </>

          <div className="row">
            <div className="col-lg-3 col-md-4">
              <h4 className="card-title">Drag &amp; Drop Event</h4>
              <div id="calendar-events" className="mb-3">
                <div className="calendar-events" data-class="bg-info">
                  <i className="fas fa-circle text-info" /> My Event One
                </div>
                <div className="calendar-events" data-class="bg-success">
                  <i className="fas fa-circle text-success" /> My Event Two
                </div>
                <div className="calendar-events" data-class="bg-danger">
                  <i className="fas fa-circle text-danger" /> My Event Three
                </div>
                <div className="calendar-events" data-class="bg-warning">
                  <i className="fas fa-circle text-warning" /> My Event Four
                </div>
              </div>
              <div className="checkbox  mb-3">
                <input id="drop-remove" className="me-1" type="checkbox" />
                <label htmlFor="drop-remove">Remove after drop</label>
              </div>
              <Link
                to="#"
                data-bs-toggle="modal"
                data-bs-target="#add_new_event"
                className="btn mb-3 btn-primary btn-block w-100"
              >
                <i className="fas fa-plus" /> Add Category
              </Link>
            </div>
            <div className="col-lg-9 col-md-8">
              <div className="card bg-white">
                <div className="card-body">
                  <FullCalendar
                    plugins={[dayGridPlugin, timeGridPlugin, interactionPlugin]}
                    headerToolbar={{
                      left: "prev,next today",
                      center: "title",
                      right: "dayGridMonth,timeGridWeek,timeGridDay",
                    }}
                    initialView="dayGridMonth"
                    editable={true}
                    selectable={true}
                    selectMirror={true}
                    dayMaxEvents={true}
                    weekends={weekendsVisible}
                    initialEvents={defaultEvents} // alternatively, use the `events` setting to fetch from a feed
                    select={handleDateSelect}
                    eventClick={(clickInfo: any) => handleEventClick(clickInfo)}
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      {/* Add Event Modal */}
      <div className="modal fade" id="add_event">
        <div className="modal-dialog modal-dialog-centered">
          <div className="modal-content">
            <div className="modal-header">
              <h4 className="modal-title">Add Event</h4>
              <button
                type="button"
                className="btn-close custom-btn-close"
                data-bs-dismiss="modal"
                aria-label="Close"
              >
                <i className="ti ti-x" />
              </button>
            </div>
            <form>
              <div className="modal-body pb-0">
                <div className="mb-3">
                  <label className="form-label">
                    Event Name <span className="text-danger">*</span>
                  </label>
                  <input className="form-control" type="text" />
                </div>
                <div className="mb-3">
                  <label className="form-label">
                    Event Date <span className="text-danger">*</span>
                  </label>
                  <div className="cal-icon">
                    <input className="form-control " type="date" />
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
                <button type="submit" className="btn btn-primary">
                  Add Event
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      {/* /Add Event Modal */}
      {/* Add Event Modal */}
      <div className="modal custom-modal fade none-border" id="my_event">
        <div className="modal-dialog modal-dialog-centered">
          <div className="modal-content">
            <div className="modal-header">
              <h4 className="modal-title">Add Event</h4>
              <button
                type="button"
                className="close"
                data-dismiss="modal"
                aria-hidden="true"
              ></button>
            </div>
            <div className="modal-body" />
            <div className="modal-footer justify-content-center">
              <button
                type="button"
                className="btn btn-success save-event submit-btn"
              >
                Create event
              </button>
              <button
                type="button"
                className="btn btn-danger delete-event submit-btn"
                data-dismiss="modal"
              >
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>
      {/* /Add Event Modal */}
      {/* Add Category Modal */}
      <div className="modal fade" id="add_new_event">
        <div className="modal-dialog modal-dialog-centered">
          <div className="modal-content">
            <div className="modal-header">
              <h4 className="modal-title">Add Category</h4>
              <button
                type="button"
                className="btn-close custom-btn-close"
                data-bs-dismiss="modal"
                aria-label="Close"
              >
                <i className="ti ti-x" />
              </button>
            </div>
            <form>
              <div className="modal-body pb-0">
                <div className="mb-3">
                  <label className="form-label">Category Name</label>
                  <input
                    className="form-control form-white"
                    placeholder="Enter name"
                    type="text"
                    name="category-name"
                  />
                </div>
                <div className="mb-3">
                  <label className="form-label">Choose Category Color</label>
                  <Select
                    className="form-white"
                    defaultValue={defaultValue}
                    options={options1}
                    placeholder="Success"
                    classNamePrefix="react-select"
                  />
                  {/* <select className="select">
              <option>Choose a color...</option>
              <option value="success">Success</option>
              <option value="danger">Danger</option>
              <option value="info">Info</option>
              <option value="primary">Primary</option>
              <option value="warning">Warning</option>
              <option value="inverse">Inverse</option>
            </select> */}
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
                <button type="submit" className="btn btn-primary">
                  Save
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      {/* /Add Category Modal */}
    </>
  );
};

export default Calendar;
