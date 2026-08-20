

const Spinner = () => {
    return (
     <>
  {/* Page Wrapper */}
  <div className="page-wrapper cardhead">
    <div className="content">
      {/* Page Header */}
      <div className="page-header">
        <div className="row">
          <div className="col-sm-12">
            <h3 className="page-title">Spinner</h3>
          </div>
        </div>
      </div>
      {/* /Page Header */}
      {/* start row */}
      <div className="row">
        <div className="col-xl-6">
          <div className="card">
            <div className="card-header">
              <h5 className="card-title">Border Spinner</h5>
            </div>
            <div className="card-body pb-2">
              <p>
                Use the border spinners for a lightweight loading indicator.
              </p>
              <div className="spinner-border m-2" role="status">
                <span className="visually-hidden">Loading...</span>
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
              <h5 className="card-title">Colors</h5>
            </div>
            <div className="card-body pb-2">
              <p>
                You can use any of our text color utilities on the standard
                spinner.
              </p>
              <div className="spinner-border text-primary m-2" role="status" />
              <div
                className="spinner-border text-secondary m-2"
                role="status"
              />
              <div className="spinner-border text-success m-2" role="status" />
              <div className="spinner-border text-danger m-2" role="status" />
              <div className="spinner-border text-warning m-2" role="status" />
              <div className="spinner-border text-info m-2" role="status" />
              <div className="spinner-border text-light m-2" role="status" />
              <div className="spinner-border text-dark m-2" role="status" />
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
              <h5 className="card-title">Growing Spinner</h5>
            </div>
            <div className="card-body pb-2">
              <p>
                If you don’t fancy a border spinner, switch to the grow spinner.
                While it doesn’t technically spin, it does repeatedly grow!
              </p>
              <div className="spinner-grow m-2" role="status">
                <span className="visually-hidden">Loading...</span>
              </div>
            </div>{" "}
            {/* end card body */}
          </div>{" "}
          {/* end card */}
        </div>{" "}
        {/* end col */}
        <div className="col-xl-6">
          <div className="card card-h-100">
            <div className="card-header">
              <h5 className="card-title">Color Growing Spinner</h5>
            </div>
            <div className="card-body pb-2">
              <p>
                You can use any of our text color utilities on the standard
                spinner.
              </p>
              <div className="spinner-grow text-primary m-2" role="status" />
              <div className="spinner-grow text-secondary m-2" role="status" />
              <div className="spinner-grow text-success m-2" role="status" />
              <div className="spinner-grow text-danger m-2" role="status" />
              <div className="spinner-grow text-warning m-2" role="status" />
              <div className="spinner-grow text-info m-2" role="status" />
              <div className="spinner-grow text-light m-2" role="status" />
              <div className="spinner-grow text-dark m-2" role="status" />
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
              <h5 className="card-title">Alignment</h5>
            </div>
            <div className="card-body">
              <p>
                Use flexbox utilities, float utilities, or text alignment
                utilities to place spinners exactly where you need them in any
                situation.
              </p>
              <div className="d-flex justify-content-center">
                <div className="spinner-border" role="status" />
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
              <h5 className="card-title">Placement</h5>
            </div>
            <div className="card-body">
              <p>
                Use <code>flexbox utilities</code>, <code>float utilities</code>
                , or <code>text alignment</code> utilities to place spinners
                exactly where you need them in any situation.
              </p>
              <div className="d-flex align-items-center">
                <strong>Loading...</strong>
                <div
                  className="spinner-border ms-auto"
                  role="status"
                  aria-hidden="true"
                />
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
              <h5 className="card-title">Size</h5>
            </div>
            <div className="card-body pb-2">
              <p>
                Add <code>.spinner-border-sm</code> and{" "}
                <code>.spinner-border.avatar-**</code> to make a smaller spinner
                that can quickly be used within other components.
              </p>
              <div className="row">
                <div className="col-lg-6">
                  <div
                    className="spinner-border avatar-lg text-primary m-2"
                    role="status"
                  />
                  <div
                    className="spinner-grow avatar-lg text-secondary m-2"
                    role="status"
                  />
                </div>{" "}
                {/* end col */}
                <div className="col-lg-6">
                  <div
                    className="spinner-border avatar-md text-primary m-2"
                    role="status"
                  />
                  <div
                    className="spinner-grow avatar-md text-secondary m-2"
                    role="status"
                  />
                </div>{" "}
                {/* end col */}
                <div className="col-lg-6">
                  <div
                    className="spinner-border avatar-sm text-primary m-2"
                    role="status"
                  />
                  <div
                    className="spinner-grow avatar-sm text-secondary m-2"
                    role="status"
                  />
                </div>{" "}
                {/* end col */}
                <div className="col-lg-6">
                  <div
                    className="spinner-border spinner-border-sm m-2"
                    role="status"
                  />
                  <div
                    className="spinner-grow spinner-grow-sm m-2"
                    role="status"
                  />
                </div>{" "}
                {/* end col */}
              </div>
              {/*end row */}
            </div>{" "}
            {/* end card body */}
          </div>{" "}
          {/* end card */}
        </div>{" "}
        {/* end col */}
        <div className="col-xl-6">
          <div className="card card-h-100">
            <div className="card-header">
              <h5 className="card-title">Buttons Spinner</h5>
            </div>
            <div className="card-body">
              <p>
                Use spinners within buttons to indicate an action is currently
                processing or taking place. You may also swap the text out of
                the spinner element and utilize button text as needed.
              </p>
              <div className="row">
                <div className="col-lg-6">
                  <div className="d-flex flex-wrap gap-2 mb-2 mb-lg-0">
                    <button
                      className="btn btn-primary"
                      type="button"
                      disabled
                    >
                      <span
                        className="spinner-border spinner-border-sm"
                        role="status"
                        aria-hidden="true"
                      />
                      <span className="visually-hidden">Loading...</span>
                    </button>
                    <button
                      className="btn btn-primary"
                      type="button"
                      disabled
                    >
                      <span
                        className="spinner-border spinner-border-sm me-1"
                        role="status"
                        aria-hidden="true"
                      />
                      Loading...
                    </button>
                  </div>
                </div>{" "}
                {/* end col */}
                <div className="col-lg-6">
                  <div className="d-flex flex-wrap gap-2">
                    <button
                      className="btn btn-primary"
                      type="button"
                      disabled
                    >
                      <span
                        className="spinner-grow spinner-grow-sm"
                        role="status"
                        aria-hidden="true"
                      />{" "}
                      <span className="visually-hidden">Loading...</span>
                    </button>
                    <button
                      className="btn btn-primary"
                      type="button"
                      disabled
                    >
                      <span
                        className="spinner-grow spinner-grow-sm me-1"
                        role="status"
                        aria-hidden="true"
                      />
                      Loading...
                    </button>
                  </div>
                </div>{" "}
                {/* end col */}
              </div>
              {/* end row */}
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
  {/* /Page Wrapper */}
</>

    )
}

export default Spinner
