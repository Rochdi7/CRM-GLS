import { Link } from "react-router"


const Collapse = () => {
  return (
   <div className="page-wrapper">
  <div className="content">
    <div className="page-header">
      <div className="page-title">
        <h3>Colors</h3>
      </div>
    </div>
    {/* start row*/}
    <div className="row">
      <div className="col-xl-6">
        <div className="card">
          <div className="card-header">
            <h5 className="card-title">Collapse</h5>
          </div>
          <div className="card-body">
            <p className="text-muted mb-3">
              Bootstrap's collapse provides the way to toggle the visibility of
              any content or element. Please read the official{" "}
              <Link
                to="https://getbootstrap.com/docs/5.2/components/collapse/"
                target="_blank"
              >
                Bootstrap
              </Link>{" "}
              documentation for a full list of options.
            </p>
            <p className="mb-0 d-flex align-items-center gap-2 flex-wrap">
              <Link
                className="btn btn-primary"
                data-bs-toggle="collapse"
                to="#collapseExample"
                aria-expanded="false"
                aria-controls="collapseExample"
              >
                Link with href
              </Link>
              <button
                className="btn btn-primary ms-1"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#collapseExample"
                aria-expanded="false"
                aria-controls="collapseExample"
              >
                Button with data-bs-target
              </button>
            </p>
            <div className="collapse show mt-3" id="collapseExample">
              <div className="card card-body mb-0">
                Anim pariatur cliche reprehenderit, enim eiusmod high life
                accusamus terry richardson ad squid. Nihil anim keffiyeh
                helvetica, craft beer labore wes anderson cred nesciunt sapiente
                ea proident.
              </div>
            </div>
          </div>{" "}
          {/* end card-body*/}
        </div>{" "}
        {/* end card*/}
      </div>{" "}
      {/* end col*/}
      <div className="col-xl-6">
        <div className="card">
          <div className="card-header">
            <h5 className="card-title">Collapse Horizontal</h5>
          </div>
          <div className="card-body">
            <p className="text-muted mb-3">
              The collapse plugin also supports horizontal collapsing. Add the{" "}
              <code>.collapse-horizontal</code> modifier class to transition the{" "}
              <code>width</code> instead of <code>height</code> and set a{" "}
              <code>width</code> on the immediate child element.
            </p>
            <p>
              <button
                className="btn btn-primary"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#collapseWidthExample"
                aria-expanded="false"
                aria-controls="collapseWidthExample"
              >
                Toggle width collapse
              </button>
            </p>
            <div style={{ minHeight: 105 }}>
              <div
                className="collapse collapse-horizontal"
                id="collapseWidthExample"
              >
                <div className="card card-body mb-0" style={{ width: 300 }}>
                  This is some placeholder content for a horizontal collapse.
                  It's hidden by default and shown when triggered.
                </div>
              </div>
            </div>
          </div>{" "}
          {/* end card-body*/}
        </div>{" "}
        {/* end card*/}
      </div>{" "}
      {/* end col*/}
    </div>
    {/* end row*/}
    {/* start row*/}
    <div className="row">
      <div className="col-xl-12">
        <div className="card">
          <div className="card-header">
            <h5 className="card-title">Multiple Targets</h5>
          </div>
          <div className="card-body">
            <p className="text-muted mb-3">
              {" "}
              Multiple <code>&lt;button&gt;</code> or <code>&lt;a&gt;</code> can
              show and hide an element if they each reference it with their{" "}
              <code>href</code> or <code>data-bs-target</code> attribute{" "}
            </p>
            <p className="mb-0 d-flex align-items-center gap-2 flex-wrap">
              <Link
                className="btn btn-primary"
                data-bs-toggle="collapse"
                to="#multiCollapseExample1"
                role="button"
                aria-expanded="false"
                aria-controls="multiCollapseExample1"
              >
                Toggle first element
              </Link>
              <button
                className="btn btn-primary"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#multiCollapseExample2"
                aria-expanded="false"
                aria-controls="multiCollapseExample2"
              >
                Toggle second element
              </button>
              <button
                className="btn btn-primary"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target=".multi-collapse"
                aria-expanded="false"
                aria-controls="multiCollapseExample1 multiCollapseExample2"
              >
                Toggle both elements
              </button>
            </p>
            {/* start row*/}
            <div className="row">
              <div className="col">
                <div
                  className="collapse multi-collapse mt-2"
                  id="multiCollapseExample1"
                >
                  <div className="card card-body mb-0">
                    Anim pariatur cliche reprehenderit, enim eiusmod high life
                    accusamus terry richardson ad squid. Nihil anim keffiyeh
                    helvetica, craft beer labore wes anderson cred nesciunt
                    sapiente ea proident.
                  </div>
                </div>
              </div>
              <div className="col">
                <div
                  className="collapse multi-collapse mt-2"
                  id="multiCollapseExample2"
                >
                  <div className="card card-body mb-0">
                    Anim pariatur cliche reprehenderit, enim eiusmod high life
                    accusamus terry richardson ad squid. Nihil anim keffiyeh
                    helvetica, craft beer labore wes anderson cred nesciunt
                    sapiente ea proident.
                  </div>
                </div>
              </div>{" "}
              {/* end col */}
            </div>
            {/* end row*/}
          </div>{" "}
          {/* end card-body*/}
        </div>{" "}
        {/* end card*/}
      </div>{" "}
      {/* end col*/}
    </div>
    {/* end row*/}
  </div>
</div>

  )
}

export default Collapse