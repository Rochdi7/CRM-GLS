import  {  useState } from "react";
import { Link } from "react-router-dom";
import CommonSelect from "../../../core/common/commonSelect";
import {
  author,
  category4,
  status,
  tags,
} from "../../../core/common/selectoption/selectoption";
import { Editor } from 'primereact/editor';
import ImageWithBasePath from "../../../core/common/imageWithBasePath";
import { all_routes } from "../../router/all_routes";
import TooltipOption from "../../../core/common/tooltipOption";

const AllBlogs = () => {
  const routes = all_routes;
  const [text1, setText1] = useState('');
  const [text2, setText2] = useState('dd');
  
  
  return (
    <div>
      {" "}
      {/* Page Wrapper */}
      <div className="page-wrapper">
        <div className="content content-two">
          <div className="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div className="my-auto mb-2">
              <h3 className="page-title mb-1">Blogs</h3>
              <nav>
                <ol className="breadcrumb mb-0">
                  <li className="breadcrumb-item">
                    <Link to={routes.adminDashboard}>Dashboard</Link>
                  </li>
                  <li className="breadcrumb-item">Pages</li>
                  <li className="breadcrumb-item active" aria-current="page">
                    Blogs
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
                  data-bs-target="#add_blog"
                >
                  <i className="ti ti-square-rounded-plus me-2" />
                  Add Blog
                </Link>
              </div>
            </div>
          </div>
          {/* Table Filter */}
          <div className="d-flex justify-content-between flex-wrap">
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
              <div className="dropdown-menu drop-width">
                <form>
                  <div className="d-flex align-items-center border-bottom p-3">
                    <h4>Filter</h4>
                  </div>
                  <div className="p-3 border-bottom">
                    <div className="row">
                      <div className="col-md-6">
                        <div className="mb-3">
                          <label className="form-label">Category</label>
                          <CommonSelect
                            className="select"
                            options={category4}
                            defaultValue={category4[0]}
                          />
                        </div>
                      </div>
                      <div className="col-md-6">
                        <div className="mb-3">
                          <label className="form-label">Author</label>
                          <CommonSelect
                            className="select"
                            options={author}
                            defaultValue={author[0]}
                          />
                        </div>
                      </div>
                      <div className="col-md-12">
                        <div className="mb-3">
                          <label className="form-label">Status</label>
                          <CommonSelect
                            className="select"
                            options={status}
                            defaultValue={status[0]}
                          />
                        </div>
                      </div>
                      <div className="col-md-12">
                        <div className="mb-0">
                          <label className="form-label">Added Date</label>
                          <input type="date" className="form-control" />
                        </div>
                      </div>
                    </div>
                  </div>
                  <div className="p-3 d-flex align-items-center justify-content-end">
                    <Link to="#" className="btn btn-light me-3">
                      Reset
                    </Link>
                    <button type="submit" className="btn btn-primary">
                      Apply
                    </button>
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
                Sort by A-Z{" "}
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
          {/* /Table Filter */}
          <div className="row">
            <div className="col-md-12">
              {/* Blog List */}
              <div className="card mb-3">
                <div className="card-body p-3 pb-0">
                  <div className="d-flex align-items-center justify-content-between flex-wrap">
                    <div className="blog-content d-flex align-items-center mb-3 me-3 w-40">
                      <Link
                        to="#"
                        className="avatar avatar-xxl rounded flex-shrink-0 me-3"
                      >
                        <ImageWithBasePath src="assets/img/blogs/blog-01.jpg" alt="img" />
                      </Link>
                      <div className="overflow-hidden">
                        <ul className="d-flex align-items-center flex-wrap row-gap-2">
                          <li className="pe-2 me-2 border-end">
                            <i className="ti ti-calendar me-1" />
                            July 2024
                          </li>
                          <li className="d-flex align-items-center">
                            <span className="avatar avatar-xs me-1">
                              <ImageWithBasePath
                                src="assets/img/profiles/avatar-27.jpg"
                                alt="img"
                              />
                            </span>
                            Jordan
                          </li>
                        </ul>
                        <h5 className="text-truncate my-2">
                          <Link to="#">
                            Personal development and self-improvement
                          </Link>
                        </h5>
                        <span className="badge badge-soft-primary fs-12">
                          Education
                        </span>
                      </div>
                    </div>
                    <div className="d-flex align-items-center flex-wrap">
                      <div className="pe-4 me-4 mb-3 border-end text-center">
                        <h6>3000</h6>
                        <p className="fs-12">Likes</p>
                      </div>
                      <div className="pe-4 me-4 mb-3 border-end text-center">
                        <h6>454</h6>
                        <p className="fs-12">Comments</p>
                      </div>
                      <div className="pe-4 me-4 mb-3 border-end text-center">
                        <h6>102</h6>
                        <p className="fs-12">Share</p>
                      </div>
                      <div className="p-0 mb-3 text-center">
                        <h6>350</h6>
                        <p className="fs-12">Reviews</p>
                      </div>
                    </div>
                    <div className="d-flex align-items-center mb-3">
                      <span className="badge badge-soft-success d-inline-flex align-items-center me-2">
                        <i className="ti ti-circle-filled fs-6 me-1" />
                        Active
                      </span>
                      <div className="dropdown">
                        <Link
                          to="#"
                          className="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                          data-bs-toggle="dropdown"
                          aria-expanded="false"
                        >
                          <i className="ti ti-dots-vertical fs-14" />
                        </Link>
                        <ul className="dropdown-menu dropdown-menu-right p-3">
                          <li>
                            <Link
                              className="dropdown-item rounded-1"
                              to="#"
                              data-bs-toggle="modal"
                              data-bs-target="#edit_blog"
                            >
                              <i className="ti ti-edit-circle me-2" />
                              Edit
                            </Link>
                          </li>
                          <li>
                            <Link
                              className="dropdown-item rounded-1"
                              to="#"
                              data-bs-toggle="modal"
                              data-bs-target="#delete-modal"
                            >
                              <i className="ti ti-trash-x me-2" />
                              Delete
                            </Link>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              {/* /Blog List */}
              {/* Blog List */}
              <div className="card mb-3">
                <div className="card-body p-3 pb-0">
                  <div className="d-flex align-items-center justify-content-between flex-wrap">
                    <div className="blog-content d-flex align-items-center mb-3 me-3 w-40">
                      <Link
                        to="#"
                        className="avatar avatar-xxl rounded flex-shrink-0 me-3"
                      >
                        <ImageWithBasePath src="assets/img/blogs/blog-02.jpg" alt="img" />
                      </Link>
                      <div className="overflow-hidden">
                        <ul className="d-flex align-items-center flex-wrap row-gap-2">
                          <li className="pe-2 me-2 border-end">
                            <i className="ti ti-calendar me-1" />
                            12 July 2024
                          </li>
                          <li className="d-flex align-items-center">
                            <span className="avatar avatar-xs me-1">
                              <ImageWithBasePath
                                src="assets/img/profiles/avatar-24.jpg"
                                alt="img"
                              />
                            </span>
                            Rubiaana
                          </li>
                        </ul>
                        <h5 className="text-truncate my-2">
                          <Link to="#">
                            5 Effective Strategies to Boost Your Productivity
                          </Link>
                        </h5>
                        <span className="badge badge-soft-primary fs-12">
                          Clear Goals
                        </span>
                      </div>
                    </div>
                    <div className="d-flex align-items-center flex-wrap">
                      <div className="pe-4 me-4 mb-3 border-end text-center">
                        <h6>1200</h6>
                        <p className="fs-12">Likes</p>
                      </div>
                      <div className="pe-4 me-4 mb-3 border-end text-center">
                        <h6>12</h6>
                        <p className="fs-12">Comments</p>
                      </div>
                      <div className="pe-4 me-4 mb-3 border-end text-center">
                        <h6>89</h6>
                        <p className="fs-12">Share</p>
                      </div>
                      <div className="p-0 mb-3 text-center">
                        <h6>56</h6>
                        <p className="fs-12">Reviews</p>
                      </div>
                    </div>
                    <div className="d-flex align-items-center mb-3">
                      <span className="badge badge-soft-success d-inline-flex align-items-center me-2">
                        <i className="ti ti-circle-filled fs-6 me-1" />
                        Active
                      </span>
                      <div className="dropdown">
                        <Link
                          to="#"
                          className="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                          data-bs-toggle="dropdown"
                          aria-expanded="false"
                        >
                          <i className="ti ti-dots-vertical fs-14" />
                        </Link>
                        <ul className="dropdown-menu dropdown-menu-right p-3">
                          <li>
                            <Link
                              className="dropdown-item rounded-1"
                              to="#"
                              data-bs-toggle="modal"
                              data-bs-target="#edit_blog"
                            >
                              <i className="ti ti-edit-circle me-2" />
                              Edit
                            </Link>
                          </li>
                          <li>
                            <Link
                              className="dropdown-item rounded-1"
                              to="#"
                              data-bs-toggle="modal"
                              data-bs-target="#delete-modal"
                            >
                              <i className="ti ti-trash-x me-2" />
                              Delete
                            </Link>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              {/* /Blog List */}
              {/* Blog List */}
              <div className="card mb-3">
                <div className="card-body p-3 pb-0">
                  <div className="d-flex align-items-center justify-content-between flex-wrap">
                    <div className="blog-content d-flex align-items-center mb-3 me-3 w-40">
                      <Link
                        to="#"
                        className="avatar avatar-xxl rounded flex-shrink-0 me-3"
                      >
                        <ImageWithBasePath src="assets/img/blogs/blog-03.jpg" alt="img" />
                      </Link>
                      <div className="overflow-hidden">
                        <ul className="d-flex align-items-center flex-wrap row-gap-2">
                          <li className="pe-2 me-2 border-end">
                            <i className="ti ti-calendar me-1" />
                            10 July 2024
                          </li>
                          <li className="d-flex align-items-center">
                            <span className="avatar avatar-xs me-1">
                              <ImageWithBasePath
                                src="assets/img/profiles/avatar-15.jpg"
                                alt="img"
                              />
                            </span>
                            Daniel
                          </li>
                        </ul>
                        <h5 className="text-truncate my-2">
                          <Link to="#">Prioritize Your Tasks</Link>
                        </h5>
                        <span className="badge badge-soft-primary fs-12">
                          Education
                        </span>
                      </div>
                    </div>
                    <div className="d-flex align-items-center flex-wrap">
                      <div className="pe-4 me-4 mb-3 border-end text-center">
                        <h6>5000</h6>
                        <p className="fs-12">Likes</p>
                      </div>
                      <div className="pe-4 me-4 mb-3 border-end text-center">
                        <h6>535</h6>
                        <p className="fs-12">Comments</p>
                      </div>
                      <div className="pe-4 me-4 mb-3 border-end text-center">
                        <h6>24</h6>
                        <p className="fs-12">Share</p>
                      </div>
                      <div className="p-0 mb-3 text-center">
                        <h6>78</h6>
                        <p className="fs-12">Reviews</p>
                      </div>
                    </div>
                    <div className="d-flex align-items-center mb-3">
                      <span className="badge badge-soft-danger d-inline-flex align-items-center me-2">
                        <i className="ti ti-circle-filled fs-6 me-1" />
                        Inactive
                      </span>
                      <div className="dropdown">
                        <Link
                          to="#"
                          className="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                          data-bs-toggle="dropdown"
                          aria-expanded="false"
                        >
                          <i className="ti ti-dots-vertical fs-14" />
                        </Link>
                        <ul className="dropdown-menu dropdown-menu-right p-3">
                          <li>
                            <Link
                              className="dropdown-item rounded-1"
                              to="#"
                              data-bs-toggle="modal"
                              data-bs-target="#edit_blog"
                            >
                              <i className="ti ti-edit-circle me-2" />
                              Edit
                            </Link>
                          </li>
                          <li>
                            <Link
                              className="dropdown-item rounded-1"
                              to="#"
                              data-bs-toggle="modal"
                              data-bs-target="#delete-modal"
                            >
                              <i className="ti ti-trash-x me-2" />
                              Delete
                            </Link>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              {/* /Blog List */}
              {/* Blog List */}
              <div className="card mb-3">
                <div className="card-body p-3 pb-0">
                  <div className="d-flex align-items-center justify-content-between flex-wrap">
                    <div className="blog-content d-flex align-items-center mb-3 me-3 w-40">
                      <Link
                        to="#"
                        className="avatar avatar-xxl rounded flex-shrink-0 me-3"
                      >
                        <ImageWithBasePath src="assets/img/blogs/blog-04.jpg" alt="img" />
                      </Link>
                      <div className="overflow-hidden">
                        <ul className="d-flex align-items-center flex-wrap row-gap-2">
                          <li className="pe-2 me-2 border-end">
                            <i className="ti ti-calendar me-1" />
                            10 July 2024
                          </li>
                          <li className="d-flex align-items-center">
                            <span className="avatar avatar-xs me-1">
                              <ImageWithBasePath
                                src="assets/img/profiles/avatar-15.jpg"
                                alt="img"
                              />
                            </span>
                            Daniel
                          </li>
                        </ul>
                        <h5 className="text-truncate my-2">
                          <Link to="#">
                            How to eliminate Eliminate Distractions
                          </Link>
                        </h5>
                        <span className="badge badge-soft-primary fs-12">
                          Advice
                        </span>
                      </div>
                    </div>
                    <div className="d-flex align-items-center flex-wrap">
                      <div className="pe-4 me-4 mb-3 border-end text-center">
                        <h6>2345</h6>
                        <p className="fs-12">Likes</p>
                      </div>
                      <div className="pe-4 me-4 mb-3 border-end text-center">
                        <h6>135</h6>
                        <p className="fs-12">Comments</p>
                      </div>
                      <div className="pe-4 me-4 mb-3 border-end text-center">
                        <h6>32</h6>
                        <p className="fs-12">Share</p>
                      </div>
                      <div className="p-0 mb-3 text-center">
                        <h6>154</h6>
                        <p className="fs-12">Reviews</p>
                      </div>
                    </div>
                    <div className="d-flex align-items-center mb-3">
                      <span className="badge badge-soft-danger d-inline-flex align-items-center me-2">
                        <i className="ti ti-circle-filled fs-6 me-1" />
                        Inactive
                      </span>
                      <div className="dropdown">
                        <Link
                          to="#"
                          className="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                          data-bs-toggle="dropdown"
                          aria-expanded="false"
                        >
                          <i className="ti ti-dots-vertical fs-14" />
                        </Link>
                        <ul className="dropdown-menu dropdown-menu-right p-3">
                          <li>
                            <Link
                              className="dropdown-item rounded-1"
                              to="#"
                              data-bs-toggle="modal"
                              data-bs-target="#edit_blog"
                            >
                              <i className="ti ti-edit-circle me-2" />
                              Edit
                            </Link>
                          </li>
                          <li>
                            <Link
                              className="dropdown-item rounded-1"
                              to="#"
                              data-bs-toggle="modal"
                              data-bs-target="#delete-modal"
                            >
                              <i className="ti ti-trash-x me-2" />
                              Delete
                            </Link>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              {/* /Blog List */}
              {/* Blog List */}
              <div className="card mb-3">
                <div className="card-body p-3 pb-0">
                  <div className="d-flex align-items-center justify-content-between flex-wrap">
                    <div className="blog-content d-flex align-items-center mb-3 me-3 w-40">
                      <Link
                        to="#"
                        className="avatar avatar-xxl rounded flex-shrink-0 me-3"
                      >
                        <ImageWithBasePath src="assets/img/blogs/blog-05.jpg" alt="img" />
                      </Link>
                      <div className="overflow-hidden">
                        <ul className="d-flex align-items-center flex-wrap row-gap-2">
                          <li className="pe-2 me-2 border-end">
                            <i className="ti ti-calendar me-1" />
                            18 July 2024
                          </li>
                          <li className="d-flex align-items-center">
                            <span className="avatar avatar-xs me-1">
                              <ImageWithBasePath
                                src="assets/img/profiles/avatar-01.jpg"
                                alt="img"
                              />
                            </span>
                            Henrita
                          </li>
                        </ul>
                        <h5 className="text-truncate my-2">
                          <Link to="#">
                            Steps to Take Regular Breaks before learning
                          </Link>
                        </h5>
                        <span className="badge badge-soft-primary fs-12">
                          Advice
                        </span>
                      </div>
                    </div>
                    <div className="d-flex align-items-center flex-wrap">
                      <div className="pe-4 me-4 mb-3 border-end text-center">
                        <h6>2454</h6>
                        <p className="fs-12">Likes</p>
                      </div>
                      <div className="pe-4 me-4 mb-3 border-end text-center">
                        <h6>165</h6>
                        <p className="fs-12">Comments</p>
                      </div>
                      <div className="pe-4 me-4 mb-3 border-end text-center">
                        <h6>22</h6>
                        <p className="fs-12">Share</p>
                      </div>
                      <div className="p-0 mb-3 text-center">
                        <h6>164</h6>
                        <p className="fs-12">Reviews</p>
                      </div>
                    </div>
                    <div className="d-flex align-items-center mb-3">
                      <span className="badge badge-soft-success d-inline-flex align-items-center me-2">
                        <i className="ti ti-circle-filled fs-6 me-1" />
                        Active
                      </span>
                      <div className="dropdown">
                        <Link
                          to="#"
                          className="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                          data-bs-toggle="dropdown"
                          aria-expanded="false"
                        >
                          <i className="ti ti-dots-vertical fs-14" />
                        </Link>
                        <ul className="dropdown-menu dropdown-menu-right p-3">
                          <li>
                            <Link
                              className="dropdown-item rounded-1"
                              to="#"
                              data-bs-toggle="modal"
                              data-bs-target="#edit_blog"
                            >
                              <i className="ti ti-edit-circle me-2" />
                              Edit
                            </Link>
                          </li>
                          <li>
                            <Link
                              className="dropdown-item rounded-1"
                              to="#"
                              data-bs-toggle="modal"
                              data-bs-target="#delete-modal"
                            >
                              <i className="ti ti-trash-x me-2" />
                              Delete
                            </Link>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              {/* /Blog List */}
              {/* Blog List */}
              <div className="card mb-3">
                <div className="card-body p-3 pb-0">
                  <div className="d-flex align-items-center justify-content-between flex-wrap">
                    <div className="blog-content d-flex align-items-center mb-3 me-3 w-40">
                      <Link
                        to="#"
                        className="avatar avatar-xxl rounded flex-shrink-0 me-3"
                      >
                        <ImageWithBasePath src="assets/img/blogs/blog-06.jpg" alt="img" />
                      </Link>
                      <div className="overflow-hidden">
                        <ul className="d-flex align-items-center flex-wrap row-gap-2">
                          <li className="pe-2 me-2 border-end">
                            <i className="ti ti-calendar me-1" />
                            18 July 2024
                          </li>
                          <li className="d-flex align-items-center">
                            <span className="avatar avatar-xs me-1">
                              <ImageWithBasePath
                                src="assets/img/profiles/avatar-02.jpg"
                                alt="img"
                              />
                            </span>
                            Zlatan
                          </li>
                        </ul>
                        <h5 className="text-truncate my-2">
                          <Link to="#">Using Time Management Techniques</Link>
                        </h5>
                        <span className="badge badge-soft-primary fs-12">
                          Clear Goals
                        </span>
                      </div>
                    </div>
                    <div className="d-flex align-items-center flex-wrap">
                      <div className="pe-4 me-4 mb-3 border-end text-center">
                        <h6>4343</h6>
                        <p className="fs-12">Likes</p>
                      </div>
                      <div className="pe-4 me-4 mb-3 border-end text-center">
                        <h6>233</h6>
                        <p className="fs-12">Comments</p>
                      </div>
                      <div className="pe-4 me-4 mb-3 border-end text-center">
                        <h6>11</h6>
                        <p className="fs-12">Share</p>
                      </div>
                      <div className="p-0 mb-3 text-center">
                        <h6>454</h6>
                        <p className="fs-12">Reviews</p>
                      </div>
                    </div>
                    <div className="d-flex align-items-center mb-3">
                      <span className="badge badge-soft-success d-inline-flex align-items-center me-2">
                        <i className="ti ti-circle-filled fs-6 me-1" />
                        Active
                      </span>
                      <div className="dropdown">
                        <Link
                          to="#"
                          className="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                          data-bs-toggle="dropdown"
                          aria-expanded="false"
                        >
                          <i className="ti ti-dots-vertical fs-14" />
                        </Link>
                        <ul className="dropdown-menu dropdown-menu-right p-3">
                          <li>
                            <Link
                              className="dropdown-item rounded-1"
                              to="#"
                              data-bs-toggle="modal"
                              data-bs-target="#edit_blog"
                            >
                              <i className="ti ti-edit-circle me-2" />
                              Edit
                            </Link>
                          </li>
                          <li>
                            <Link
                              className="dropdown-item rounded-1"
                              to="#"
                              data-bs-toggle="modal"
                              data-bs-target="#delete-modal"
                            >
                              <i className="ti ti-trash-x me-2" />
                              Delete
                            </Link>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              {/* /Blog List */}
              <div className="text-center">
                <Link
                  to="#"
                  className="btn btn-primary d-inline-flex align-items-center"
                >
                  <i className="ti ti-loader-3 me-2" />
                  Load More
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
      {/* /Page Wrapper */}
      {/* Add Blog */}
      <div className="modal fade" id="add_blog">
        <div className="modal-dialog modal-dialog-centered  modal-lg">
          <div className="modal-content">
            <div className="modal-header">
              <h4 className="modal-title">New Blogs</h4>
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
              <div className="modal-body">
                <div className="row">
                  <div className="col-md-12">
                    <div className="mb-3">
                      <label className="form-label">Blog Title</label>
                      <input type="text" className="form-control" />
                    </div>
                    <label className="form-label">Featured Image</label>
                    <div className="d-flex align-items-center upload-pic flex-wrap row-gap-3 mb-3">
                      <div className="d-flex align-items-center justify-content-center avatar avatar-xxl border border-dashed me-2 flex-shrink-0 text-dark frames">
                        <i className="ti ti-photo-plus fs-16" />
                      </div>
                      <div className="profile-upload">
                        <div className="profile-uploader d-flex align-items-center">
                          <div className="drag-upload-btn mb-3">
                            Upload
                            <input
                              type="file"
                              className="form-control image-sign"
                              multiple
                            />
                          </div>
                          <Link to="#" className="btn btn-primary mb-3">
                            Remove
                          </Link>
                        </div>
                        <p>Upload image size 4MB, Format JPG, PNG, SVG</p>
                      </div>
                    </div>
                  </div>
                  <div className="col-lg-4 col-md-6">
                    <div className="mb-3">
                      <label className="form-label">Category</label>
                      <CommonSelect
                        className="select"
                        options={category4}
                        defaultValue={category4[0]}
                      />
                    </div>
                  </div>
                  <div className="col-lg-4 col-md-6">
                    <div className="mb-3">
                      <label className="form-label">Tags</label>
                      <CommonSelect
                        className="select"
                        options={tags}
                        defaultValue={tags[0]}
                      />
                    </div>
                  </div>
                  <div className="col-lg-4 col-md-6">
                    <div className="mb-3">
                      <label className="form-label">Status</label>
                      <CommonSelect
                        className="select"
                        options={status}
                        defaultValue={status[0]}
                      />
                    </div>
                  </div>
                  <div className="col-md-12">
                    <div className="mb-0">
                      <label className="form-label">Description</label>
                      <div className="summernote" />
                      <Editor value={text1} onTextChange={(e) => setText1(e.htmlValue ?? '')} style={{ height: '130px' }} />
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
                <button type="submit" className="btn btn-primary">
                  Add Blog
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      {/* /Add Blog */}
      {/* Edit Blog */}
      <div className="modal fade" id="edit_blog">
        <div className="modal-dialog modal-dialog-centered  modal-lg">
          <div className="modal-content">
            <div className="modal-header">
              <h4 className="modal-title">Edit Blogs</h4>
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
              <div className="modal-body">
                <div className="row">
                  <div className="col-md-12">
                    <div className="mb-3">
                      <label className="form-label">Blog Title</label>
                      <input
                        type="text"
                        className="form-control"
                        placeholder="Enter Title"
                        defaultValue="Personal development and self-improvement"
                      />
                    </div>
                    <label className="form-label">Featured Image</label>
                    <div className="d-flex align-items-center upload-pic flex-wrap row-gap-3 mb-3">
                      <div className="d-flex align-items-center justify-content-center avatar avatar-xxl border border-dashed me-2 flex-shrink-0 text-dark frames">
                        <i className="ti ti-photo-plus fs-16" />
                      </div>
                      <div className="profile-upload">
                        <div className="profile-uploader d-flex align-items-center">
                          <div className="drag-upload-btn mb-3">
                            Upload
                            <input
                              type="file"
                              className="form-control image-sign"
                              multiple
                            />
                          </div>
                          <Link to="#" className="btn btn-primary mb-3">
                            Remove
                          </Link>
                        </div>
                        <p>Upload image size 4MB, Format JPG, PNG, SVG</p>
                      </div>
                    </div>
                  </div>
                  <div className="col-lg-4 col-md-6">
                    <div className="mb-3">
                      <label className="form-label">Category</label>
                      <CommonSelect
                        className="select"
                        options={category4}
                        defaultValue={category4[0]}
                      />
                    </div>
                  </div>
                  <div className="col-lg-4 col-md-6">
                    <div className="mb-3">
                      <label className="form-label">Tags</label>
                      <CommonSelect
                        className="select"
                        options={tags}
                        defaultValue={tags[0]}
                      />
                    </div>
                  </div>
                  <div className="col-lg-4 col-md-6">
                    <div className="mb-3">
                      <label className="form-label">Status</label>
                      <CommonSelect
                        className="select"
                        options={status}
                        defaultValue={status[0]}
                      />
                    </div>
                  </div>
                  <div className="col-md-12">
                    <div className="mb-0">
                      <label className="form-label">Description</label>
                      <div className="summernote">
                       
                      </div>
                      <Editor value={text2}  onTextChange={(e) => setText2(e.htmlValue ?? '')} style={{ height: '130px' }} />
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
                <button type="submit" className="btn btn-primary">
                  Save Changes
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      {/* /Edit Blog */}
    </div>
  );
};

export default AllBlogs;
