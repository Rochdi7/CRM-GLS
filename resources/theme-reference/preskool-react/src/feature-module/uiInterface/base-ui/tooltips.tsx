
import { Tooltip } from "antd";
import { Link } from "react-router-dom";

const Tooltips = () => {
  return (
    <>
      {/* Page Wrapper */}
      <div className="page-wrapper cardhead">
        <div className="content ">
          {/* Page Header */}
          <div className="page-header">
            <div className="row">
              <div className="col-sm-12">
                <h3 className="page-title">Tooltip</h3>
              </div>
            </div>
          </div>
          {/* /Page Header */}
          {/* start row */}
          <div className="row">
            <div className="col-xl-6">
              <div className="card">
                <div className="card-header">
                  <h5 className="card-title">Tooltips on links</h5>
                </div>
                <div className="card-body">
                  <p>Hover over the links below to see tooltips.</p>
                  <p className="muted mb-0">
                    Placeholder text to demonstrate some{" "}
                    <Link to="#" className="text-primary">
                      <Tooltip title="Default tooltip">inline links</Tooltip>
                    </Link>{" "}
                    with tooltips. This is now just filler, no killer. Content
                    placed here just to mimic the presence of{" "}
                    <Link to="#" className="text-primary">
                      <Tooltip title="Another tooltip">real text</Tooltip>
                    </Link>
                    . And all that just to give you an idea of how tooltips
                    would look when used in real-world situations. So hopefully
                    you've now seen how{" "}
                    <Link to="#" className="text-primary">
                      <Tooltip title="Another one here too">
                        these tooltips on links
                      </Tooltip>
                    </Link>{" "}
                    can work in practice, once you use them on{" "}
                    <Link to="#" className="text-primary">
                      <Tooltip title="The last tip!">your own</Tooltip>
                    </Link>{" "}
                    site or project.
                  </p>
                </div>{" "}
                {/* end card body */}
              </div>{" "}
              {/* end card */}
              <div className="card">
                <div className="card-header">
                  <h5 className="card-title">Disabled Elements</h5>
                </div>
                <div className="card-body">
                  <p className="text-muted">
                    Elements with the <code>disabled</code> attribute aren’t
                    interactive, meaning users cannot focus, hover, or click
                    them to trigger a tooltip (or popover). As a workaround,
                    you’ll want to trigger the tooltip from a wrapper{" "}
                    <code>&lt;div&gt;</code> or <code>&lt;span&gt;</code>,
                    ideally made keyboard-focusable using{" "}
                    <code>tabindex="0"</code>, and override the{" "}
                    <code>pointer-events</code> on the disabled element.
                  </p>
                  <div>
                    <span className="d-inline-block" tabIndex={0}>
                      <button
                        className="btn btn-primary pe-none"
                        type="button"
                        disabled
                      >
                        Disabled button
                      </button>
                    </span>
                  </div>
                </div>{" "}
                {/* end card body */}
              </div>{" "}
              {/* end card */}
              <div className="card">
                <div className="card-header">
                  <h5 className="card-title">Hover Elements</h5>
                </div>
                <div className="card-body">
                  <p className="text-muted">
                    Elements with the <code>disabled</code> attribute aren’t
                    interactive, meaning users cannot focus, hover, or click
                    them to trigger a tooltip (or popover). As a workaround,
                    you’ll want to trigger the tooltip from a wrapper{" "}
                    <code>&lt;div&gt;</code> or <code>&lt;span&gt;</code>,
                    ideally made keyboard-focusable using{" "}
                    <code>tabindex="0"</code>, and override the{" "}
                    <code>pointer-events</code> on the disabled element.
                  </p>
                  <button className="btn btn-primary" type="button">
                    <Tooltip title="Hover Only, Not a Focus">Hover</Tooltip>
                  </button>
                </div>{" "}
                {/* end card body */}
              </div>{" "}
              {/* end card */}
            </div>{" "}
            {/* end col */}
            <div className="col-xl-6">
              <div className="card">
                <div className="card-header">
                  <h5 className="card-title">Directions</h5>
                </div>
                <div className="card-body">
                  <p>
                    Hover over the buttons below to see the four tooltips
                    directions: top, right, bottom, and left.
                  </p>
                  <div className="d-flex flex-wrap gap-2">
                    <button type="button" className="btn btn-primary">
                      <Tooltip placement="top" title="Tooltip on top">
                        Tooltip on top
                      </Tooltip>
                    </button>
                    <button type="button" className="btn btn-primary">
                      <Tooltip placement="bottom" title="Tooltip on bottom">
                        Tooltip on bottom
                      </Tooltip>
                    </button>
                    <button type="button" className="btn btn-primary">
                      <Tooltip placement="left" title="Tooltip on left">
                        Tooltip on left
                      </Tooltip>
                    </button>
                    <button type="button" className="btn btn-primary">
                      <Tooltip placement="right" title="Tooltip on right">
                        Tooltip on right
                      </Tooltip>
                    </button>
                  </div>
                </div>{" "}
                {/* end card body */}
              </div>{" "}
              {/* end card */}
              <div className="card">
                <div className="card-header">
                  <h5 className="card-title">HTML Tags</h5>
                </div>
                <div className="card-body">
                  <p className="text-muted">And with custom HTML added:</p>
                  <button type="button" className="btn btn-secondary me-1">
                    <Tooltip
                      title={
                        <>
                          <em>Tooltip</em> <u>with</u> <b>HTML</b>
                        </>
                      }
                    >
                      Tooltip with HTML
                    </Tooltip>
                  </button>
                  <button type="button" className="btn btn-primary">
                    <Tooltip
                      trigger="click"
                      placement="bottom"
                      title={
                        <>
                          <em>Tooltip</em> <u>with</u> <b>HTML</b>
                        </>
                      }
                    >
                      Click Me
                    </Tooltip>
                  </button>
                </div>{" "}
                {/* end card body */}
              </div>{" "}
              {/* end card */}
              <div className="card">
                <div className="card-header">
                  <h5 className="card-title">Color Tooltips</h5>
                </div>
                <div className="card-body">
                  <p className="text-muted">
                    We set a custom class with ex.
                    <code>data-bs-custom-class="primary-tooltip"</code> to scope
                    our background-color primary appearance and use it to
                    override a local CSS variable.
                  </p>
                  <div className="d-flex flex-wrap gap-2">
                    <Tooltip
                      title="This top tooltip is themed via CSS variables."
                      placement="top"
                      overlayInnerStyle={{
                        backgroundColor: "#FE9F43",
                        color: "#ffffff",
                      }}
                    >
                      <button type="button" className="btn btn-primary">
                        Primary tooltip
                      </button>
                    </Tooltip>
                    <Tooltip
                      title="This top tooltip is themed via CSS variables."
                      placement="top"
                      overlayInnerStyle={{
                        backgroundColor: "rgb(219.3, 0, 0)",
                        color: "#ffffff",
                      }}
                    >
                      <button type="button" className="btn btn-danger">
                        Danger tooltip
                      </button>
                    </Tooltip>
                    <Tooltip
                      title="This top tooltip is themed via CSS variables."
                      placement="top"
                      overlayInnerStyle={{
                        backgroundColor: "rgb(14.3552, 79.8508, 209.9448)",
                        color: "#ffffff",
                      }}
                    >
                      <button type="button" className="btn btn-info">
                        Info tooltip
                      </button>
                    </Tooltip>
                    <Tooltip
                      title="This top tooltip is themed via CSS variables."
                      placement="top"
                      overlayInnerStyle={{
                        backgroundColor:
                          "rgb(52.9657142857, 156.3342857143, 109.3485714286)",
                        color: "#ffffff",
                      }}
                    >
                      <button type="button" className="btn btn-success">
                        Success tooltip
                      </button>
                    </Tooltip>
                    <Tooltip
                      title="This top tooltip is themed via CSS variables."
                      placement="top"
                      overlayInnerStyle={{
                        backgroundColor: "rgb(5.22, 25.52, 44.08)",
                        color: "#ffffff",
                      }}
                    >
                      <button type="button" className="btn btn-secondary">
                        Secondary tooltip
                      </button>
                    </Tooltip>
                    <Tooltip
                      title="This top tooltip is themed via CSS variables."
                      placement="top"
                      overlayInnerStyle={{
                        backgroundColor: "rgb(243.3, 187.4779220779, 0) ",
                        color: "#ffffff",
                      }}
                    >
                      <button type="button" className="btn btn-warning">
                        Warning tooltip
                      </button>
                    </Tooltip>
                    <Tooltip
                      title="This top tooltip is themed via CSS variables."
                      placement="top"
                      overlayInnerStyle={{
                        backgroundColor:
                          "rgb(17.991588785, 26.6542056075, 53.308411215)",
                        color: "#ffffff",
                      }}
                    >
                      <button type="button" className="btn btn-dark">
                        Dark tooltip
                      </button>
                    </Tooltip>
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
      {/* /Page Wrapper */}
    </>
  );
};

export default Tooltips;
