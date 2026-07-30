import  { useRef, useState } from "react";
import { staffAttendance } from "../../../core/data/json/staff-attendance";
import type { TableData } from "../../../core/data/interface";
import { Link } from "react-router-dom";
import ImageWithBasePath from "../../../core/common/imageWithBasePath";
import Table from "../../../core/common/dataTable/index";
import PredefinedDateRanges from "../../../core/common/datePicker";
import CommonSelect from "../../../core/common/commonSelect";
import {
  departmentName,
  designationName,
  studentName,
  teacherId,
} from "../../../core/common/selectoption/selectoption";
import { all_routes } from "../../router/all_routes";
import TooltipOption from "../../../core/common/tooltipOption";

const StaffAttendance = () => {
  const routes = all_routes;
  const data = staffAttendance;
  const [selectedOptions, setSelectedOptions] = useState(
    data.map(() => 'Present') // Default to 'Present' for each row
  );
  const dropdownMenuRef = useRef<HTMLDivElement | null>(null);
  const handleApplyClick = () => {
    if (dropdownMenuRef.current) {
      dropdownMenuRef.current.classList.remove("show");
    }
  };

  // Handle state change for each row
  const handleOptionChange = (index:any, value:any) => {
    const newSelectedOptions = [...selectedOptions];
    newSelectedOptions[index] = value;
    setSelectedOptions(newSelectedOptions);
  };
  const columns = [
    {
      title: "ID",
      dataIndex: "id",
      render: ( record: any) => (
        <>
          <Link to="#" className="link-primary">
            {record.id}
          </Link>
        </>
      ),
      sorter: (a: TableData, b: TableData) => a.id.length - b.id.length,
    },
    {
      title: "Name",
      dataIndex: "name",
      render: (text: string, record: any) => (
        <div className="d-flex align-items-center">
          <Link to="#" className="avatar avatar-md">
            <ImageWithBasePath
              src={record.img}
              className="img-fluid rounded-circle"
              alt="img"
            />
          </Link>
          <div className="ms-2">
            <p className="text-dark mb-0">
              <Link to="#">{text}</Link>
            </p>
          </div>
        </div>
      ),
      sorter: (a: TableData, b: TableData) => a.name.length - b.name.length,
    },
    {
      title: "Department",
      dataIndex: "department",
      sorter: (a: TableData, b: TableData) =>
        a.department.length - b.department.length,
    },
    {
      title: "Designation",
      dataIndex: "designation",
      sorter: (a: TableData, b: TableData) =>
        a.designation.length - b.designation.length,
    },
    {
      title: "Attendance",
      dataIndex: "attendance",
      render: (index:any) => (
        <div className="d-flex align-items-center check-radio-group flex-nowrap">
          <label className="custom-radio">
            <input
              type="radio"
              name={`attendance-${index}`}
              value="Present"
              checked={selectedOptions[index] === 'Present'}
              onChange={() => handleOptionChange(index, 'Present')}
            />
            <span className="checkmark" />
            Present
          </label>
          <label className="custom-radio">
            <input
              type="radio"
              name={`attendance-${index}`}
              value="Late"
              checked={selectedOptions[index] === 'Late'}
              onChange={() => handleOptionChange(index, 'Late')}
            />
            <span className="checkmark" />
            Late
          </label>
          <label className="custom-radio">
            <input
              type="radio"
              name={`attendance-${index}`}
              value="Absent"
              checked={selectedOptions[index] === 'Absent'}
              onChange={() => handleOptionChange(index, 'Absent')}
            />
            <span className="checkmark" />
            Absent
          </label>
          <label className="custom-radio">
            <input
              type="radio"
              name={`attendance-${index}`}
              value="Holiday"
              checked={selectedOptions[index] === 'Holiday'}
              onChange={() => handleOptionChange(index, 'Holiday')}
            />
            <span className="checkmark" />
            Holiday
          </label>
          <label className="custom-radio">
            <input
              type="radio"
              name={`attendance-${index}`}
              value="Halfday"
              checked={selectedOptions[index] === 'Halfday'}
              onChange={() => handleOptionChange(index, 'Halfday')}
            />
            <span className="checkmark" />
            Halfday
          </label>
        </div>
      ),
      sorter: (a: TableData, b: TableData) => a.attendance.length - b.attendance.length,
    },
    {
      title: "Notes",
      dataIndex: "notes",
      render: () => (
          <input
            type="text"
            className="form-control"
            placeholder="Enter Name" style={{ minWidth: '200px', width: '200px' }}
          />
      ),
      sorter: (a: TableData, b: TableData) => a.notes.length - b.notes.length,
    },
  ];
  return (
    <div>
      <div className="page-wrapper">
        <div className="content">
          {/* Page Header */}
          <div className="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div className="my-auto mb-2">
              <h3 className="page-title mb-1">Staff Attendance</h3>
              <nav>
                <ol className="breadcrumb mb-0">
                  <li className="breadcrumb-item">
                    <Link to={routes.adminDashboard}>Dashboard</Link>
                  </li>
                  <li className="breadcrumb-item">
                    <Link to="#">Report</Link>
                  </li>
                  <li className="breadcrumb-item active" aria-current="page">
                    Staff Attendance
                  </li>
                </ol>
              </nav>
            </div>
            <div className="d-flex my-xl-auto right-content align-items-center flex-wrap">
            <TooltipOption />
            </div>
          </div>
          {/* /Page Header */}
          {/* Student List */}
          <div className="card">
            <div className="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
              <h4 className="mb-3">Staff Attendance List</h4>
              <div className="d-flex align-items-center flex-wrap">
                <div className="input-icon-start mb-3 me-2 position-relative">
                  <PredefinedDateRanges />
                </div>
                <div className="dropdown mb-3 me-2">
                  <Link
                    to="#"
                    className="btn btn-outline-light bg-white dropdown-toggle"
                    data-bs-toggle="dropdown"
                    data-bs-auto-close="outside"
                  >
                    <i className="ti ti-filter me-2" />
                    Filter
                  </Link>
                  <div className="dropdown-menu drop-width"  ref={dropdownMenuRef}>
                    <form>
                      <div className="d-flex align-items-center border-bottom p-3">
                        <h4>Filter</h4>
                      </div>
                      <div className="p-3 border-bottom">
                        <div className="row">
                          <div className="col-md-12">
                            <div className="mb-3">
                              <label className="form-label">ID</label>
                              <CommonSelect
                                className="select"
                                options={teacherId}
                              />
                            </div>
                          </div>
                          <div className="col-md-12">
                            <div className="mb-3">
                              <label className="form-label">Name</label>

                              <CommonSelect
                                className="select"
                                options={studentName}
                              />
                            </div>
                          </div>
                          <div className="col-md-6">
                            <div className="mb-0">
                              <label className="form-label">Department</label>
                              <CommonSelect
                                className="select"
                                options={departmentName}
                              />
                            </div>
                          </div>
                          <div className="col-md-6">
                            <div className="mb-0">
                              <label className="form-label">Designation</label>
                              <CommonSelect
                                className="select"
                                options={designationName}
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
                <div className="dropdown mb-3">
                  <Link
                    to="#"
                    className="btn btn-outline-light bg-white dropdown-toggle"
                    data-bs-toggle="dropdown"
                  >
                    <i className="ti ti-sort-ascending-2 me-2" />
                    Sort by A-Z
                  </Link>
                  <ul className="dropdown-menu p-3">
                    <li>
                      <Link to="#" className="dropdown-item rounded-1 active">
                        Ascending
                      </Link>
                    </li>
                    <li>
                      <Link to="#" className="dropdown-item rounded-1">
                        Descending
                      </Link>
                    </li>
                    <li>
                      <Link to="#" className="dropdown-item rounded-1">
                        Recently Viewed
                      </Link>
                    </li>
                    <li>
                      <Link to="#" className="dropdown-item rounded-1">
                        Recently Added
                      </Link>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
            <div className="card-body p-0 py-3">
              {/* Student List */}
                <Table dataSource={data} columns={columns} Selection={true} />
              {/* /Student List */}
            </div>
          </div>
          {/* /Student List */}
        </div>
      </div>
    </div>
  );
};

export default StaffAttendance;
