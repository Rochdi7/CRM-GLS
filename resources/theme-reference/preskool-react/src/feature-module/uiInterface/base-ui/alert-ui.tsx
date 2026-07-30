
import { Link } from 'react-router-dom'


const AlertUi = () => {
  return (
    <div className="page-wrapper">
  <div className="content">
    <div className="page-header">
      <div className="page-title">
        <h3>Alerts</h3>
      </div>
    </div>
    {/* start row */}
    <div className="row">
      <div className="col-xl-6">
        <div className="card">
          <div className="card-header">
            <h5 className="card-title">Default Alert</h5>
          </div>
          <div className="card-body">
            <p className="text-muted">
              For proper styling, use one of the eight
              <strong>required</strong> contextual classes (e.g.,
              <code>.alert-success</code>). For background color use class
              <code>.bg-* </code>, <code>.text-white </code>
            </p>
            <div className="alert alert-primary" role="alert">
              <strong>Primary - </strong> A simple primary alert—check it out!
            </div>
            <div className="alert alert-secondary" role="alert">
              <strong>Secondary - </strong> A simple secondary alert—check it
              out!
            </div>
            <div className="alert alert-success" role="alert">
              <strong>Success - </strong> A simple success alert—check it out!
            </div>
            <div className="alert alert-danger" role="alert">
              <strong>Danger - </strong> A simple danger alert—check it out!
            </div>
            <div className="alert alert-warning" role="alert">
              <strong>Warning - </strong> A simple warning alert—check it out!
            </div>
            <div className="alert alert-info" role="alert">
              <strong>Info - </strong> A simple info alert—check it out!
            </div>
            <div className="alert alert-light" role="alert">
              <strong>Light - </strong> A simple light alert—check it out!
            </div>
            <div className="alert alert-dark mb-0" role="alert">
              <strong>Dark - </strong> A simple dark alert—check it out!
            </div>
          </div>{" "}
          {/* end card body */}
        </div>{" "}
        {/* end card */}
      </div>{" "}
      {/* end col */}
      <div className="col-xl-6">
        <div className="card">
          <div className="card-header">
            <h5 className="card-title">Dismissing Alert</h5>
          </div>
          <div className="card-body">
            <p className="text-muted">
              Add a dismiss button and the <code>.alert-dismissible</code>{" "}
              class, which adds extra padding to the right of the alert and
              positions the <code>.btn-close</code> button.
            </p>
            <div
              className="alert alert-primary text-bg-primary alert-dismissible"
              role="alert"
            >
              <strong>Primary - </strong> A simple primary alert — check
              <button
                type="button"
                className="btn-close btn-close-white"
                data-bs-dismiss="alert"
                aria-label="Close"
              />
            </div>
            <div
              className="alert alert-secondary text-bg-secondary alert-dismissible"
              role="alert"
            >
              <strong>Secondary - </strong> A simple secondary alert — check
              <button
                type="button"
                className="btn-close btn-close-white"
                data-bs-dismiss="alert"
                aria-label="Close"
              />
            </div>
            <div
              className="alert alert-success text-bg-success alert-dismissible"
              role="alert"
            >
              <button
                type="button"
                className="btn-close btn-close-white"
                data-bs-dismiss="alert"
                aria-label="Close"
              />
              <strong>Success - </strong> A simple success alert — check
            </div>
            <div
              className="alert alert-danger text-bg-danger alert-dismissible"
              role="alert"
            >
              <button
                type="button"
                className="btn-close btn-close-white"
                data-bs-dismiss="alert"
                aria-label="Close"
              />
              <strong>Error - </strong> A simple danger alert — check it
            </div>
            <div
              className="alert alert-warning text-bg-warning alert-dismissible"
              role="alert"
            >
              <button
                type="button"
                className="btn-close btn-close-white"
                data-bs-dismiss="alert"
                aria-label="Close"
              />
              <strong>Warning - </strong> A simple warning alert—check
            </div>
            <div
              className="alert alert-info text-bg-info alert-dismissible"
              role="alert"
            >
              <button
                type="button"
                className="btn-close btn-close-white"
                data-bs-dismiss="alert"
                aria-label="Close"
              />
              <strong>Info - </strong> A simple info alert—check it out!
            </div>
            <div
              className="alert alert-light text-bg-light alert-dismissible"
              role="alert"
            >
              <button
                type="button"
                className="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
              />
              <strong>Light - </strong> A simple light alert—check it
            </div>
            <div
              className="alert alert-dark text-bg-dark alert-dismissible mb-0"
              role="alert"
            >
              <button
                type="button"
                className="btn-close btn-close-white"
                data-bs-dismiss="alert"
                aria-label="Close"
              />
              <strong>Dark - </strong> A simple dark alert—check it out!
            </div>
          </div>{" "}
          {/* end card body */}
        </div>{" "}
        {/* end card */}
      </div>{" "}
      {/* end col */}
    </div>
    {/* end row */}
    {/* start row */}
    <div className="row">
      <div className="col-xl-6">
        <div className="card">
          <div className="card-header">
            <h5 className="card-title">Link Color</h5>
          </div>
          <div className="card-body">
            <p className="text-muted">
              Use the <code>.alert-link</code> utility class to quickly provide
              matching colored links within any alert.
            </p>
            <div className="alert alert-primary" role="alert">
              A simple primary alert with{" "}
              <Link to="#" className="alert-link">
                an example link
              </Link>
              . Give it a click if you like.
            </div>
            <div className="alert alert-secondary" role="alert">
              A simple secondary alert with{" "}
              <Link to="#" className="alert-link">
                an example link
              </Link>
              . Give it a click if you like.
            </div>
            <div className="alert alert-success" role="alert">
              A simple success alert with{" "}
              <Link to="#" className="alert-link">
                an example link
              </Link>
              . Give it a click if you like.
            </div>
            <div className="alert alert-danger" role="alert">
              A simple danger alert with{" "}
              <Link to="#" className="alert-link">
                an example link
              </Link>
              . Give it a click if you like.
            </div>
            <div className="alert alert-warning" role="alert">
              A simple warning alert with{" "}
              <Link to="#" className="alert-link">
                an example link
              </Link>
              . Give it a click if you like.
            </div>
            <div className="alert alert-info" role="alert">
              A simple info alert with{" "}
              <Link to="#" className="alert-link">
                an example link
              </Link>
              . Give it a click if you like.
            </div>
            <div className="alert alert-light" role="alert">
              A simple light alert with{" "}
              <Link to="#" className="alert-link">
                an example link
              </Link>
              . Give it a click if you like.
            </div>
            <div className="alert alert-dark mb-0" role="alert">
              A simple dark alert with{" "}
              <Link to="#" className="alert-link">
                an example link
              </Link>
              . Give it a click if you like.
            </div>
          </div>{" "}
          {/* end card body */}
        </div>{" "}
        {/* end card */}
      </div>{" "}
      {/* end col */}
      <div className="col-xl-6">
        <div className="card">
          <div className="card-header">
            <h5 className="card-title">Alerts with Border</h5>
          </div>
          <div className="card-body">
            <p className="text-muted">
              Display alert with transparent background and with contextual text
              color. Use classes <code>.bg-white</code>, and{" "}
              <code>.text-*</code>. E.g. <code>bg-white text-primary</code>.
            </p>
            <div
              className="alert alert-primary alert-dismissible border border-primary"
              role="alert"
            >
              <button
                type="button"
                className="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
              />
              <strong>Primary - </strong> A simple primary alert — check it out!
            </div>
            <div
              className="alert alert-secondary alert-dismissible border border-secondary"
              role="alert"
            >
              <button
                type="button"
                className="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
              />
              <strong>Secondary - </strong> A simple secondary alert — check it
              out!
            </div>
            <div
              className="alert alert-success alert-dismissible border border-success"
              role="alert"
            >
              <button
                type="button"
                className="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
              />
              <strong>Success - </strong> A simple success alert — check it out!
            </div>
            <div
              className="alert alert-danger alert-dismissible border border-danger"
              role="alert"
            >
              <button
                type="button"
                className="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
              />
              <strong>Error - </strong> A simple danger alert — check it out!
            </div>
            <div
              className="alert alert-warning alert-dismissible border border-warning"
              role="alert"
            >
              <button
                type="button"
                className="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
              />
              <strong>Warning - </strong> A simple warning alert—check it out!
            </div>
            <div
              className="alert alert-info alert-dismissible border border-info"
              role="alert"
            >
              <button
                type="button"
                className="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
              />
              <strong>Info - </strong> A simple info alert—check it out!
            </div>
            <div
              className="alert alert-light alert-dismissible border border-light"
              role="alert"
            >
              <button
                type="button"
                className="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
              />
              <strong>Light - </strong> A simple light alert—check it out!
            </div>
            <div
              className="alert alert-dark alert-dismissible border border-dark mb-0"
              role="alert"
            >
              <button
                type="button"
                className="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
              />
              <strong>Dark - </strong> A simple dark alert—check it out!
            </div>
          </div>{" "}
          {/* end card body */}
        </div>{" "}
        {/* end card */}
      </div>{" "}
      {/* end col */}
    </div>
    {/* end row */}
    {/* start row */}
    <div className="row">
      <div className="col-xl-6">
        <div className="card">
          <div className="card-header">
            <h5 className="card-title">Additional Content</h5>
          </div>
          <div className="card-body">
            <p className="text-muted">
              Alerts can also contain additional HTML elements like headings,
              paragraphs and dividers.
            </p>
            <div className="alert alert-success p-3" role="alert">
              <h4 className="alert-heading">Well done!</h4>
              <p>
                Aww yeah, you successfully read this important alert message.
                This example text is going to run a bit longer so that you can
                see how spacing within an alert works with this kind of content.
              </p>
              <hr className="border-success border-opacity-25" />
              <p className="mb-0">
                Whenever you need to, be sure to use margin utilities to keep
                things nice and tidy.
              </p>
            </div>
            <div className="alert alert-secondary p-3 d-flex" role="alert">
              <i className="ti ti-mood-smile fs-1 me-2" />
              <div>
                <h4 className="alert-heading">Well done!</h4>
                <p>
                  Aww yeah, you successfully read this important alert message.
                  This example text is going to run a bit longer so that you can
                  see how spacing within an alert works with this kind of
                  content.
                </p>
                <hr className="border-secondary border-opacity-25" />
                <p className="mb-0">
                  Whenever you need to, be sure to use margin utilities to keep
                  things nice and tidy.
                </p>
              </div>
            </div>
            <div className="alert alert-primary d-flex p-3 mb-0" role="alert">
              <i className="ti ti-mood-smile fs-1 me-2" />
              <div>
                <h4 className="alert-heading">Thank you!</h4>
                <p>
                  Aww yeah, you successfully read this important alert message.
                  This example text is going to run a bit longer so that you can
                  see how spacing within an alert works with this kind of
                  content..
                </p>
                <button type="button" className="btn btn-primary btn-sm">
                  Close
                </button>
              </div>
            </div>
          </div>{" "}
          {/* end card body */}
        </div>{" "}
        {/* end card */}
      </div>{" "}
      {/* end col */}
      <div className="col-xl-6">
        <div className="card">
          <div className="card-header">
            <h5 className="card-title">Live Alert</h5>
          </div>
          <div className="card-body">
            <p className="text-muted">
              Click the button below to show an alert (hidden with inline styles
              to start), then dismiss (and destroy) it with the built-in close
              button.
            </p>
            <div id="liveAlertPlaceholder" />
            <button type="button" className="btn btn-primary" id="liveAlertBtn">
              Show live alert
            </button>
          </div>{" "}
          {/* end card body */}
        </div>{" "}
        {/* end card */}
        <div className="card">
          <div className="card-header">
            <h5 className="card-title">Alert with Icons</h5>
          </div>
          <div className="card-body">
            <div
              className="alert alert-primary d-flex align-items-center"
              role="alert"
            >
              <i className="ti ti-mood-smile f-12 me-2" />
              <div>An example alert with an icon</div>
            </div>
            <div
              className="alert alert-secondary d-flex align-items-center"
              role="alert"
            >
              <i className="ti ti-triangles f-12 me-2" />
              <div>An example alert with an icon</div>
            </div>
            <div
              className="alert alert-success d-flex align-items-center"
              role="alert"
            >
              <i className="ti ti-checks f-12 me-2" />
              <div>An example alert with an icon</div>
            </div>
            <div
              className="alert alert-danger d-flex align-items-center"
              role="alert"
            >
              <i className="ti ti-trash-x f-12 me-2" />
              <div>An example alert with an icon</div>
            </div>
            <div
              className="alert alert-warning d-flex align-items-center"
              role="alert"
            >
              <i className="ti ti-info-triangle f-12 me-2" />
              <div>An example alert with an icon</div>
            </div>
            <div
              className="alert alert-info d-flex align-items-center"
              role="alert"
            >
              <i className="ti ti-info-circle  f-12 me-2" />
              <div>An example alert with an icon</div>
            </div>
            <div
              className="alert alert-light d-flex align-items-center"
              role="alert"
            >
              <i className="ti ti-triangles f-12 me-2" />
              <div>An example alert with an icon</div>
            </div>
            <div
              className="alert alert-dark d-flex align-items-center mb-0"
              role="alert"
            >
              <i className="ti ti-refresh f-12 me-2" />
              <div>An example alert with an icon</div>
            </div>
          </div>{" "}
          {/* end card body */}
        </div>{" "}
        {/* end card */}
      </div>{" "}
      {/* end col */}
    </div>
    {/* end row */}
  </div>
</div>


  )
}

export default AlertUi