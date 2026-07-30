
import { Link } from "react-router-dom";
import PredefinedDateRanges from "../../../core/common/datePicker";
import CommonSelect from "../../../core/common/commonSelect";
import {
  category4,
  date,
  publish,
  status,
  tags,
} from "../../../core/common/selectoption/selectoption";
import { blog_comments_data } from "../../../core/data/json/blog_comments_data";
import type { TableData } from "../../../core/data/interface";
import Table from "../../../core/common/dataTable/index";
import { all_routes } from "../../router/all_routes";
import TooltipOption from "../../../core/common/tooltipOption";

const BlogComments = () => {
  const data = blog_comments_data;
  const routes = all_routes;
  const columns = [
    {
      title: "Comment",
      dataIndex: "comment",
      key: "comment",
      sorter: (a: TableData, b: TableData) => a.comment.length - b.comment.length,
    },
    {
      title: "Date",
      dataIndex: "date",
      key: "date",
      sorter: (a: TableData, b: TableData) => a.date.length - b.date.length,
      className: "text-gray-9",
    },
    {
      title: "Review",
      dataIndex: "review",
      key: "review",
      sorter: (a: TableData, b: TableData) => a.review.length - b.review.length,
      render: () => (
        <div className="rating">
          <i className="ti ti-star-filled filled" />
          <i className="ti ti-star-filled filled" />
          <i className="ti ti-star-filled filled" />
          <i className="ti ti-star-filled filled" />
          <i className="ti ti-star-filled filled" />
          <span className="rating-rate">5.0</span>
        </div>
      ),
    },
    {
      title: "Blog",
      dataIndex: "blog",
      key: "blog",
      sorter: (a: TableData, b: TableData) => a.blog.length - b.blog.length,
      className: "text-gray-9 text-content",
    },
    {
      title: "By",
      dataIndex: "author",
      key: "author",
      sorter: (a: TableData, b: TableData) => a.author.length - b.author.length,
    },
    {
      title: "Action",
      dataIndex: "action",
      key: "action",
      sorter: (a: TableData, b: TableData) => a.action.length - b.action.length,
      render: () => (
        <CommonSelect
          className="select"
          options={publish}
          defaultValue={publish[0]}
        />
      ),
    },
    {
      title: "",
      dataIndex: "delete",
      key: "delete",
      render: () => (
        <Link
          to="#"
          data-bs-toggle="modal"
          data-bs-target="#delete-modal"
          className="delete-icon"
        >
          <i className="ti ti-trash-x" />
        </Link>
      ),
    },
  ];

  return (
    <div>
      {" "}
      {/* Page Wrapper */}
      <div className="page-wrapper">
        <div className="content">
          {/* Page Header */}
          <div className="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div className="my-auto mb-2">
              <h3 className="page-title mb-1">Comments</h3>
              <nav>
                <ol className="breadcrumb mb-0">
                  <li className="breadcrumb-item">
                    <Link to={routes.adminDashboard}>Dashboard</Link>
                  </li>
                  <li className="breadcrumb-item">
                    <Link to="#">Pages</Link>
                  </li>
                  <li className="breadcrumb-item active" aria-current="page">
                    Comments
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
          {/* /Page Header */}
          <div className="card">
            <div className="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
              <h4 className="mb-3">Comments List</h4>
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
                  <div className="dropdown-menu drop-width">
                    <form>
                      <div className="d-flex align-items-center border-bottom p-3">
                        <h4>Filter</h4>
                      </div>
                      <div className="p-3 border-bottom">
                        <div className="row">
                          <div className="col-md-12">
                            <div className="mb-0">
                              <label className="form-label">Date</label>
                              <CommonSelect
                                className="select"
                                options={date}
                                defaultValue={date[0]}
                              />
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
              {/* /Comments List */}
                <Table dataSource={data} columns={columns} Selection={true} />
              {/* /Comments List */}
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
                        Introduction: Are you struggling to stay focused and get
                        things done? Do you find yourself procrastinating or
                        feeling overwhelmed by your workload? You're not alone.
                        In today's fast-paced world, maintaining productivity
                        can be a challenge. But fear not! In this article, we'll
                        share five proven strategies to help you boost your
                        productivity and reclaim control of your time. 1. Set
                        Clear Goals: One of the most effective ways to increase
                        productivity is to set clear, achievable goals. Start by
                        defining what you want to accomplish, and break down
                        your goals into smaller, manageable tasks. This will
                        help you stay focused and motivated as you work towards
                        achieving your objectives. 2. Prioritize Your Tasks: Not
                        all tasks are created equal. To maximize your
                        productivity, it's essential to prioritize your tasks
                        based on their importance and urgency. Identify the most
                        critical tasks that align with your goals and focus your
                        time and energy on completing them first. This will
                        prevent you from getting bogged down by less important
                        tasks and ensure that you're making progress towards
                        your objectives. 3. Eliminate Distractions: Distractions
                        can derail your productivity and prevent you from
                        staying focused on your work. Identify common
                        distractions in your environment, such as social media,
                        email notifications, or noisy surroundings, and take
                        steps to minimize or eliminate them. This could involve
                        turning off notifications, setting designated work
                        hours, or creating a distraction-free workspace where
                        you can concentrate without interruption. 4. Use Time
                        Management Techniques: Effective time management is key
                        to enhancing productivity. Experiment with different
                        time management techniques, such as the Pomodoro
                        Technique, time blocking, or the Eisenhower Matrix, to
                        find a system that works best for you. By allocating
                        your time wisely and staying organized, you can maximize
                        your efficiency and accomplish more in less time. 5.
                        Take Regular Breaks: Contrary to popular belief, taking
                        regular breaks can actually improve productivity.
                        Research has shown that short breaks can help refresh
                        your mind, prevent burnout, and enhance focus and
                        concentration. Incorporate short breaks into your
                        workday, and use them to rest, recharge, and rejuvenate.
                        Whether it's going for a walk, practicing mindfulness,
                        or simply taking a few deep breaths, find activities
                        that help you relax and unwind before diving back into
                        your work. Conclusion: Boosting productivity is not an
                        overnight process, but with dedication and consistent
                        effort, you can develop habits and strategies that will
                        help you work smarter, not harder. By setting clear
                        goals, prioritizing tasks, eliminating distractions,
                        managing your time effectively, and taking regular
                        breaks, you can unlock your full potential and achieve
                        greater success in both your personal and professional
                        life.
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
                <button type="submit" className="btn btn-primary">
                  Save Changes
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      {/* /Edit Blog */}
      {/* Delete Modal */}
      <div className="modal fade" id="delete-modal">
        <div className="modal-dialog modal-dialog-centered">
          <div className="modal-content">
            <form>
              <div className="modal-body text-center">
                <span className="delete-icon">
                  <i className="ti ti-trash-x" />
                </span>
                <h4>Confirm Deletion</h4>
                <p>
                  You want to delete all the marked items, this cant be undone
                  once you delete.
                </p>
                <div className="d-flex justify-content-center">
                  <Link
                    to="#"
                    className="btn btn-light me-3"
                    data-bs-dismiss="modal"
                  >
                    Cancel
                  </Link>
                  <button type="submit" className="btn btn-danger">
                    Yes, Delete
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
      {/* /Delete Modal */}
    </div>
  );
};

export default BlogComments;
