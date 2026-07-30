

const Grid = () => {
  return (
<div className="page-wrapper">
  <div className="content">
    <div className="page-header">
      <div className="page-title">
        <h3>Grid</h3>
      </div>
    </div>
    {/* start row */}
    <div className="row">
      <div className="col-12">
        <div className="card">
          <div className="card-header">
            <h5 className="card-title">Grid Options</h5>
          </div>
          <div className="card-body">
            <p className="text-muted">
              See how aspects of the Bootstrap grid system work across multiple
              devices with a handy table.
            </p>
            <div className="table-responsive">
              <table className="table table-nowrap table-bordered table-striped mb-0">
                <thead>
                  <tr>
                    <th />
                    <th className="text-center">
                      Extra small
                      <br />
                      <small>&lt;576px</small>
                    </th>
                    <th className="text-center">
                      Small
                      <br />
                      <small>≥576px</small>
                    </th>
                    <th className="text-center">
                      Medium
                      <br />
                      <small>≥768px</small>
                    </th>
                    <th className="text-center">
                      Large
                      <br />
                      <small>≥992px</small>
                    </th>
                    <th className="text-center">
                      Extra Large
                      <br />
                      <small>≥1200px</small>
                    </th>
                    <th className="text-center">
                      Extra Large
                      <br />
                      <small>≥1400px</small>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <th className="table-nowrap" scope="row">
                      Container <code className="fw-normal">max-width</code>
                    </th>
                    <td>None (auto)</td>
                    <td>540px</td>
                    <td>720px</td>
                    <td>960px</td>
                    <td>1140px</td>
                    <td>1320px</td>
                  </tr>
                  <tr>
                    <th className="table-nowrap" scope="row">
                      Class prefix
                    </th>
                    <td>
                      <code>.col-</code>
                    </td>
                    <td>
                      <code>.col-sm-</code>
                    </td>
                    <td>
                      <code>.col-md-</code>
                    </td>
                    <td>
                      <code>.col-lg-</code>
                    </td>
                    <td>
                      <code>.col-xl-</code>
                    </td>
                    <td>
                      <code>.col-xxl-</code>
                    </td>
                  </tr>
                  <tr>
                    <th className="table-nowrap" scope="row">
                      # of columns
                    </th>
                    <td colSpan={6}>12</td>
                  </tr>
                  <tr>
                    <th className="table-nowrap" scope="row">
                      Gutter width
                    </th>
                    <td colSpan={6}>2rem (1rem on left and right)</td>
                  </tr>
                  <tr>
                    <th className="table-nowrap" scope="row">
                      Custom gutters
                    </th>
                    <td colSpan={6}>Yes</td>
                  </tr>
                  <tr>
                    <th className="table-nowrap" scope="row">
                      Nestable
                    </th>
                    <td colSpan={6}>Yes</td>
                  </tr>
                  <tr>
                    <th className="table-nowrap" scope="row">
                      Column ordering
                    </th>
                    <td colSpan={6}>Yes</td>
                  </tr>
                </tbody>
              </table>
            </div>{" "}
            {/* end table responsive */}
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
      <div className="col-12">
        <div className="card">
          <div className="card-header">
            <h5 className="card-title">Grid Example</h5>
          </div>
          <div className="card-body pb-2">
            <p className="text-muted">
              You may use predefined grid classes. Using
              <code>.col-lg-* </code>you can set the grid system.
            </p>
            <div className="grid-structure">
              <div className="row">
                <div className="col-lg-12">
                  <div className="grid-container">col-lg-12</div>
                </div>
              </div>
              <div className="row">
                <div className="col-lg-11">
                  <div className="grid-container">col-lg-11</div>
                </div>
                <div className="col-lg-1">
                  <div className="grid-container">col-lg-1</div>
                </div>
              </div>
              <div className="row">
                <div className="col-lg-10">
                  <div className="grid-container">col-lg-10</div>
                </div>
                <div className="col-lg-2">
                  <div className="grid-container">col-lg-2</div>
                </div>
              </div>
              <div className="row">
                <div className="col-lg-9">
                  <div className="grid-container">col-lg-9</div>
                </div>
                <div className="col-lg-3">
                  <div className="grid-container">col-lg-3</div>
                </div>
              </div>
              <div className="row">
                <div className="col-lg-8">
                  <div className="grid-container">col-lg-8</div>
                </div>
                <div className="col-lg-4">
                  <div className="grid-container">col-lg-4</div>
                </div>
              </div>
              <div className="row">
                <div className="col-lg-7">
                  <div className="grid-container">col-lg-7</div>
                </div>
                <div className="col-lg-5">
                  <div className="grid-container">col-lg-5</div>
                </div>
              </div>
              <div className="row">
                <div className="col-lg-6">
                  <div className="grid-container">col-lg-6</div>
                </div>
                <div className="col-lg-6">
                  <div className="grid-container">col-lg-6</div>
                </div>
              </div>
              <div className="row">
                <div className="col-lg-5">
                  <div className="grid-container">col-lg-5</div>
                </div>
                <div className="col-lg-7">
                  <div className="grid-container">col-lg-7</div>
                </div>
              </div>
              <div className="row">
                <div className="col-lg-4">
                  <div className="grid-container">col-lg-4</div>
                </div>
                <div className="col-lg-8">
                  <div className="grid-container">col-lg-8</div>
                </div>
              </div>
              <div className="row">
                <div className="col-lg-3">
                  <div className="grid-container">col-lg-3</div>
                </div>
                <div className="col-lg-9">
                  <div className="grid-container">col-lg-9</div>
                </div>
              </div>
              <div className="row">
                <div className="col-lg-2">
                  <div className="grid-container">col-lg-2</div>
                </div>
                <div className="col-lg-10">
                  <div className="grid-container">col-lg-10</div>
                </div>
              </div>
              <div className="row">
                <div className="col-lg-1">
                  <div className="grid-container">col-lg-1</div>
                </div>
                <div className="col-lg-11">
                  <div className="grid-container">col-lg-11</div>
                </div>
              </div>
              <div className="row">
                <div className="col-lg-2">
                  <div className="grid-container">col-lg-2</div>
                </div>
                <div className="col-lg-3">
                  <div className="grid-container">col-lg-3</div>
                </div>
                <div className="col-lg-4">
                  <div className="grid-container">col-lg-4</div>
                </div>
                <div className="col-lg-2">
                  <div className="grid-container">col-lg-2</div>
                </div>
                <div className="col-lg-1">
                  <div className="grid-container">col-lg-1</div>
                </div>
              </div>{" "}
              {/* end row */}
            </div>{" "}
            {/* grid structure */}
          </div>{" "}
          {/* end card body*/}
        </div>{" "}
        {/* end card*/}
      </div>{" "}
      {/* end col*/}
    </div>
    {/* end row*/}
  </div>
</div>

  );
};

export default Grid;
