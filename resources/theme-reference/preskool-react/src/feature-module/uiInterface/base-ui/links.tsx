import { Link } from "react-router"

const Links = () => {
  return (
   <div className="page-wrapper cardhead">
  <div className="content ">
    {/* Page Header */}
    <div className="page-header">
      <div className="row">
        <div className="col-sm-12">
          <h3 className="page-title">Links</h3>
        </div>
      </div>
    </div>
    {/* /Page Header */}
    {/* start row */}
    <div className="row">
      <div className="col-xl-6">
        <div className="card">
          <div className="card-header">
            <h5 className="card-title">Colored Links</h5>
          </div>
          <div className="card-body">
            <p className="text-muted">
              You can use the <code>.link-*</code> classes to colorize links.
              Unlike the <code>.text-*</code> classes, these classes have a{" "}
              <code>:hover</code> and <code>:focus</code> state. Some of the
              link styles use a relatively light foreground color, and should
              only be used on a dark background in order to have sufficient
              contrast.
            </p>
            <p>
              <Link to="#" className="link-primary">
                Primary link
              </Link>
            </p>
            <p>
              <Link to="#" className="link-secondary">
                Secondary link
              </Link>
            </p>
            <p>
              <Link to="#" className="link-success">
                Success link
              </Link>
            </p>
            <p>
              <Link to="#" className="link-danger">
                Danger link
              </Link>
            </p>
            <p>
              <Link to="#" className="link-warning">
                Warning link
              </Link>
            </p>
            <p>
              <Link to="#" className="link-info">
                Info link
              </Link>
            </p>
            <p>
              <Link to="#" className="link-light">
                Light link
              </Link>
            </p>
            <p>
              <Link to="#" className="link-dark">
                Dark link
              </Link>
            </p>
            <p className="mb-0">
              <Link to="#" className="link-body-emphasis">
                Emphasis link
              </Link>
            </p>
          </div>{" "}
          {/* end card body */}
        </div>{" "}
        {/* end card */}
      </div>{" "}
      {/* end col */}
      <div className="col-xl-6">
        <div className="card card-h-100">
          <div className="card-header">
            <h5 className="card-title">Link Utilities</h5>
          </div>
          <div className="card-body">
            <p className="text-muted">
              Colored link helpers have been updated to pair with our link
              utilities. Use the new utilities to modify the link opacity,
              underline opacity, and underline offset.
            </p>
            <p>
              <Link
                to="#"
                className="link-primary text-decoration-underline link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover"
              >
                Primary link
              </Link>
            </p>
            <p>
              <Link
                to="#"
                className="link-secondary text-decoration-underline link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover"
              >
                Secondary link
              </Link>
            </p>
            <p>
              <Link
                to="#"
                className="link-success text-decoration-underline link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover"
              >
                Success link
              </Link>
            </p>
            <p>
              <Link
                to="#"
                className="link-danger text-decoration-underline link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover"
              >
                Danger link
              </Link>
            </p>
            <p>
              <Link
                to="#"
                className="link-warning text-decoration-underline link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover"
              >
                Warning link
              </Link>
            </p>
            <p>
              <Link
                to="#"
                className="link-info text-decoration-underline link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover"
              >
                Info link
              </Link>
            </p>
            <p>
              <Link
                to="#"
                className="link-light text-decoration-underline link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover"
              >
                Light link
              </Link>
            </p>
            <p>
              <Link
                to="#"
                className="link-dark text-decoration-underline link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover"
              >
                Dark link
              </Link>
            </p>
            <p className="mb-0">
              <Link
                to="#"
                className="link-body-emphasis text-decoration-underline link-offset-2 link-underline-opacity-25 link-underline-opacity-75-hover"
              >
                Emphasis link
              </Link>
            </p>
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
            <h5 className="card-title">Link Opacity</h5>
          </div>
          <div className="card-body">
            <p className="text-muted">
              Change the alpha opacity of the link <code>rgba()</code> color
              value with utilities. Please be aware that changes to a color’s
              opacity can lead to links with <em>insufficient</em> contrast.
            </p>
            <p>
              <Link className="link-opacity-10" to="#">
                Link opacity 10
              </Link>
            </p>
            <p>
              <Link className="link-opacity-25" to="#">
                Link opacity 25
              </Link>
            </p>
            <p>
              <Link className="link-opacity-50" to="#">
                Link opacity 50
              </Link>
            </p>
            <p>
              <Link className="link-opacity-75" to="#">
                Link opacity 75
              </Link>
            </p>
            <p className="mb-0">
              <Link className="link-opacity-100" to="#">
                Link opacity 100
              </Link>
            </p>
          </div>{" "}
          {/* end card body */}
        </div>{" "}
        {/* end card */}
      </div>{" "}
      {/* end col */}
      <div className="col-xl-6">
        <div className="card card-h-100">
          <div className="card-header">
            <h5 className="card-title">Link Hover Opacity</h5>
          </div>
          <div className="card-body">
            <p className="text-muted">
              You can even change the opacity level on hover.
            </p>
            <p>
              <Link className="link-opacity-10-hover" to="#">
                Link hover opacity 10
              </Link>
            </p>
            <p>
              <Link className="link-opacity-25-hover" to="#">
                Link hover opacity 25
              </Link>
            </p>
            <p>
              <Link className="link-opacity-50-hover" to="#">
                Link hover opacity 50
              </Link>
            </p>
            <p>
              <Link className="link-opacity-75-hover" to="#">
                Link hover opacity 75
              </Link>
            </p>
            <p className="mb-0">
              <Link className="link-opacity-100-hover" to="#">
                Link hover opacity 100
              </Link>
            </p>
          </div>{" "}
          {/* end card body */}
        </div>{" "}
        {/* end card */}
      </div>{" "}
      {/* end col */}
    </div>
    {/*end row*/}
    {/* start row */}
    <div className="row">
      <div className="col-xl-6">
        <div className="card">
          <div className="card-header">
            <h5 className="card-title">Underline Color</h5>
          </div>
          <div className="card-body">
            <p className="text-muted">
              Change the underline’s color independent of the link text color.
            </p>
            <p>
              <Link
                to="#"
                className="text-decoration-underline link-underline-primary"
              >
                Primary underline
              </Link>
            </p>
            <p>
              <Link
                to="#"
                className="text-decoration-underline link-underline-secondary"
              >
                Secondary underline
              </Link>
            </p>
            <p>
              <Link
                to="#"
                className="text-decoration-underline link-underline-success"
              >
                Success underline
              </Link>
            </p>
            <p>
              <Link
                to="#"
                className="text-decoration-underline link-underline-danger"
              >
                Danger underline
              </Link>
            </p>
            <p>
              <Link
                to="#"
                className="text-decoration-underline link-underline-warning"
              >
                Warning underline
              </Link>
            </p>
            <p>
              <Link
                to="#"
                className="text-decoration-underline link-underline-info"
              >
                Info underline
              </Link>
            </p>
            <p>
              <Link
                to="#"
                className="text-decoration-underline link-underline-light"
              >
                Light underline
              </Link>
            </p>
            <p className="mb-0">
              <Link
                to="#"
                className="text-decoration-underline link-underline-dark"
              >
                Dark underline
              </Link>
            </p>
          </div>{" "}
          {/* end card body */}
        </div>{" "}
        {/* end card */}
      </div>{" "}
      {/* end col */}
      <div className="col-xl-6">
        <div className="card card-h-100">
          <div className="card-header">
            <h5 className="card-title">Underline Opacity</h5>
          </div>
          <div className="card-body">
            <p className="text-muted">
              Change the underline’s opacity. Requires adding{" "}
              <code>.link-underline</code> to first set an <code>rgba()</code>{" "}
              color we use to then modify the alpha opacity.
            </p>
            <p>
              <Link
                className="text-decoration-underline link-offset-2 link-underline link-underline-opacity-0"
                to="#"
              >
                Underline opacity 0
              </Link>
            </p>
            <p>
              <Link
                className="text-decoration-underline link-offset-2 link-underline link-underline-opacity-10"
                to="#"
              >
                Underline opacity 10
              </Link>
            </p>
            <p>
              <Link
                className="text-decoration-underline link-offset-2 link-underline link-underline-opacity-25"
                to="#"
              >
                Underline opacity 25
              </Link>
            </p>
            <p>
              <Link
                className="text-decoration-underline link-offset-2 link-underline link-underline-opacity-50"
                to="#"
              >
                Underline opacity 50
              </Link>
            </p>
            <p>
              <Link
                className="text-decoration-underline link-offset-2 link-underline link-underline-opacity-75"
                to="#"
              >
                Underline opacity 75
              </Link>
            </p>
            <p className="mb-0">
              <Link
                className="text-decoration-underline link-offset-2 link-underline link-underline-opacity-100"
                to="#"
              >
                Underline opacity 100
              </Link>
            </p>
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
            <h5 className="card-title">Underline Offset</h5>
          </div>
          <div className="card-body">
            <p className="text-muted">
              Change the underline’s opacity. Requires adding{" "}
              <code>.link-underline</code> to first set an <code>rgba()</code>{" "}
              color we use to then modify the alpha opacity.
            </p>
            <p>
              <Link to="#">Default link</Link>
            </p>
            <p>
              <Link className="text-decoration-underline link-offset-1" to="#">
                Offset 1 link
              </Link>
            </p>
            <p>
              <Link className="text-decoration-underline link-offset-2" to="#">
                Offset 2 link
              </Link>
            </p>
            <p className="mb-0">
              <Link className="text-decoration-underline link-offset-3" to="#">
                Offset 3 link
              </Link>
            </p>
          </div>{" "}
          {/* end card body */}
        </div>{" "}
        {/* end card */}
      </div>{" "}
      {/* end col */}
      <div className="col-xl-6">
        <div className="card card-h-100">
          <div className="card-header">
            <h5 className="card-title">Hover Variants</h5>
          </div>
          <div className="card-body">
            <p className="text-muted">
              Just like the <code>.link-opacity-*-hover</code> utilities,{" "}
              <code>.link-offset</code> and <code>.link-underline-opacity</code>{" "}
              utilities include <code>:hover</code> variants by default. Mix and
              match to create unique link styles.
            </p>
            <Link
              className="link-offset-2 link-offset-3-hover text-decoration-underline link-underline link-underline-opacity-0 link-underline-opacity-75-hover"
              to="#"
            >
              Underline opacity 0
            </Link>
          </div>{" "}
          {/* end card body */}
        </div>{" "}
        {/* end card */}
      </div>{" "}
      {/* end col */}
    </div>
    {/*end row */}
  </div>
</div>

  )
}

export default Links