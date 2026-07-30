
import { Link } from "react-router-dom";
import { callhistorydata } from "../../../core/data/json/callHistoryData";
import ImageWithBasePath from "../../../core/common/imageWithBasePath";
import Table from "../../../core/common/dataTable/index";
import { all_routes } from "../../router/all_routes";

const CallHistory = () => {
  const routes = all_routes;
  const data = callhistorydata;
  const columns = [
    {
      title: "UserName",
      dataIndex: "username",
      render: (text: any, record: any) => (
        <span className="userimgname">
          <Link to="#" className="product-img">
            <ImageWithBasePath alt="" src={record.image_url} />
          </Link>
          <Link to="#">{text}</Link>
        </span>
      ),
      sorter: (a: any, b: any) => a.username.length - b.username.length,
    },
    {
      title: "Phone Number",
      dataIndex: "phone_number",
      sorter: (a: any, b: any) => a.phone_number.length - b.phone_number.length,
    },

    {
      title: "Call Type",
      dataIndex: "call_type",
      sorter: (a: any, b: any) => a.call_type.length - b.call_type.length,
    },

    {
      title: "Phone",
      dataIndex: "phone",
      sorter: (a: any, b: any) => a.phone.length - b.phone.length,
    },

    {
      title: "Duration",
      dataIndex: "duration",
      sorter: (a: any, b: any) => a.duration.length - b.duration.length,
    },
    {
      title: "Date & Time",
      dataIndex: "date_time",
      sorter: (a: any, b: any) => a.duration.length - b.duration.length,
    },
    {
      title: "Actions",
      dataIndex: "actions",
      render: () => (
        <div className="dropdown table-action">
          <Link
            to="#"
            className="action-icon"
            data-bs-toggle="dropdown"
            aria-expanded="true"
          >
            <i className="fa fa-ellipsis-v"></i>
          </Link>
          <div
            className="dropdown-menu dropdown-menu-right"
            data-bs-toggle="modal"
            data-bs-target="#user-profile-new"
            style={{
              position: "absolute",
              inset: "0px auto auto 0px",
              margin: "0px",
              transform: "translate3d(-99.3333px, 35.3333px, 0px)",
            }}
            data-popper-placement="bottom-start"
          >
            <Link className="dropdown-item edit-popup" to="#">
              <i className="ti ti-edit text-blue"></i> Edit
            </Link>
            <Link
              className="dropdown-item"
              to="#"
              data-bs-toggle="modal"
              data-bs-target="#delete_campaign"
            >
              <i className="ti ti-trash text-danger"></i> Delete
            </Link>
          </div>
        </div>
      ),
    },
  ];

  return (
    <div className="page-wrapper">
      <div className="content">
        <div className="page-header">
          <div className="page-header menu">
            <div className="page-title">
              <h4>Call History</h4>
              <h6>Manage your products</h6>
            </div>
          </div>
        </div>
        {/* /product list */}
        <div className="card table-list-card">
          <div className="card-body">
            {/* /Filter */}
            <div className="table-responsive product-list">
              <Table columns={columns} dataSource={data} />
            </div>
          </div>
        </div>
        {/* /product list */}
      </div>
      {/* details popup */}
      <div className="modal fade" id="user-profile-new">
        <div className="modal-dialog history-modal-profile">
          <div className="modal-content">
            <div className="page-wrapper details-blk">
              <div className="content">
                <div className="text-center right-sidebar-profile mb-3">
                  <figure className="avatar">
                    <ImageWithBasePath
                      src="assets/img/users/user-23.jpg"
                      alt="img"
                    />
                  </figure>
                  <div className="chat-options chat-option-profile">
                    <ul className="list-inline">
                      <li className="list-inline-item">
                        <Link
                          to={routes.audioCall}
                          className="btn btn-outline-light "
                          data-bs-toggle="tooltip"
                          data-bs-placement="bottom"
                          title=""
                          data-bs-original-title="Voice Call"
                        >
                          <i className="bx bx-phone" />
                        </Link>
                      </li>
                      <li className="list-inline-item">
                        <Link
                          to={routes.chat}
                          className="btn btn-outline-light"
                          data-bs-toggle="tooltip"
                          data-bs-placement="bottom"
                          title=""
                          data-bs-original-title="Chat"
                        >
                          <i className="bx bx-message-square-dots" />
                        </Link>
                      </li>
                      <li className="list-inline-item ">
                        <Link
                          to={routes.videoCall}
                          className="btn btn-outline-light profile-open"
                          data-bs-toggle="tooltip"
                          data-bs-placement="bottom"
                          title=""
                          data-bs-original-title="Video Call"
                        >
                          <i className="bx bx-video" />
                        </Link>
                      </li>
                    </ul>
                  </div>
                </div>
                <div className="modal-profile-detail">
                  <div className="row">
                    <div className="col-lg-6">
                      <div className="modal-userlist">
                        <ul>
                          <li>
                            Name<span>Thomas</span>
                          </li>
                          <li>
                            Phone<span>+1 25182 94528</span>
                          </li>
                          <li>
                            Email<span>thomas@example.com</span>
                          </li>
                        </ul>
                      </div>
                    </div>
                    <div className="col-lg-6">
                      <div className="modal-userlist d-flex justify-content-center">
                        <ul>
                          <li>
                            Total Calls<span>20</span>
                          </li>
                          <li>
                            Average Call Timing<span>0.30</span>
                          </li>
                          <li>
                            Average Waiting Time<span>00.5</span>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      {/* /details popup */}
      <div
        className="modal custom-modal fade"
        id="delete_campaign"
        role="dialog"
      >
        <div className="modal-dialog modal-dialog-centered">
          <div className="modal-content">
            <div className="modal-header border-0 m-0 justify-content-end">
              <button
                className="btn-close"
                data-bs-dismiss="modal"
                aria-label="Close"
              >
                <i className="ti ti-x" />
              </button>
            </div>
            <div className="modal-body">
              <div className="success-message text-center">
                <div className="success-popup-icon">
                  <i className="ti ti-trash-x" />
                </div>
                <h3>Are you sure?</h3>
                <p className="del-info">You won't be able to revert this!</p>
                <div className="col-lg-12 text-center modal-btn">
                  <Link
                    to="#"
                    className="btn btn-light"
                    data-bs-dismiss="modal"
                  >
                    Cancel
                  </Link>
                  <Link
                    to="#"
                    className="btn btn-danger"
                    data-bs-dismiss="modal"
                  >
                    Yes, Delete it
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default CallHistory;
