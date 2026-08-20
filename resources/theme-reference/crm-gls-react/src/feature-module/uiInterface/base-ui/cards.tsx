
import { Link } from "react-router-dom";
import ImageWithBasePath from "../../../core/common/imageWithBasePath";

const Cards = () => {
  return (
   <div className="page-wrapper">
  <div className="content">
    <div className="page-header">
      <div className="page-title">
        <h3>Cards</h3>
      </div>
    </div>
    <div className="row">
      <div className="col-lg-4">
        <div className="card">
          <div className="card-header">
            <h5 className="card-title">Card Default</h5>
          </div>
          <div className="card-body">
            <ImageWithBasePath
              className="card-img-top pb-2 rounded-0"
              src="assets/img/media/img-8.jpg"
              alt="Card image cap"
            />
            <h5 className="card-title mb-2">Card title</h5>
            <p className="card-text">
              Some quick example text to build on the card..
            </p>
          </div>
        </div>
      </div>
      <div className="col-lg-4">
        <div className="card">
          <div className="card-header">
            <h5 className="card-title">With Link</h5>
          </div>
          <div className="card-body">
            <ImageWithBasePath
              className="card-img-top pb-2 rounded-0"
              src="assets/img/media/img-8.jpg"
              alt="Card image cap"
            />
            <h5 className="card-title mb-2">Card title</h5>
            <p className="card-text">
              Some quick example text to build on the card..
            </p>
            <Link
              to="#"
              className="card-link text-decoration-underline text-primary"
            >
              View More
            </Link>
          </div>
        </div>
      </div>
      <div className="col-lg-4">
        <div className="card">
          <div className="card-header">
            <h5 className="card-title">With Link</h5>
          </div>
          <div className="card-body">
            <ImageWithBasePath
              className="card-img-top pb-2 rounded-0"
              src="assets/img/media/img-8.jpg"
              alt="Card image cap"
            />
            <h5 className="card-title mb-2">Card title</h5>
            <p className="card-text">
              Some quick example text to build on the card..
            </p>
            <Link to="#" className="btn btn-primary">
              Go Somewhere
            </Link>
          </div>
        </div>
      </div>
      <div className="col-lg-12">
        <div className="card">
          <div className="card-body">
            <div className="row">
              <div className="col-lg-6">
                <div className="card">
                  <div className="card-body">
                    <h5 className="card-title">Card title</h5>
                    <p className="card-text">
                      Some quick example text to build on the card title and
                      make up the bulk of the card's content.
                    </p>
                    <Link
                      to="#"
                      className="card-link text-decoration-underline text-primary"
                    >
                      Card link
                    </Link>
                    <Link
                      to="#"
                      className="card-link text-decoration-underline text-primary"
                    >
                      Another link
                    </Link>
                  </div>
                </div>
              </div>
              <div className="col-lg-6">
                <div className="card mb-0">
                  <div className="card-body">
                    <h5 className="card-title">Card title</h5>
                    <h6 className="card-subtitle mb-2 text-muted">
                      Card subtitle
                    </h6>
                    <p className="card-text">
                      Some quick example text to build on the card title and
                      make up the bulk of the card's content.
                    </p>
                    <Link
                      to="#"
                      className="card-link text-decoration-underline text-primary"
                    >
                      Card link
                    </Link>
                    <Link
                      to="#"
                      className="card-link text-decoration-underline text-primary"
                    >
                      Another link
                    </Link>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div className="col-lg-4">
        <div className="card">
          <div className="card-header">
            <h5 className="card-title">Header</h5>
          </div>
          <div className="card-body">
            <div className="card mb-0">
              <div className="card-header bg-light">
                <h5 className="card-title">Card Header</h5>
              </div>
              <div className="card-body">
                <h6 className="mb-2">Special title treatment</h6>
                <p className="card-text">
                  With supporting text below as a natural lead-in to additional
                  content.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div className="col-lg-4">
        <div className="card">
          <div className="card-header">
            <h5 className="card-title">Footer</h5>
          </div>
          <div className="card-body">
            <div className="card mb-0">
              <div className="card-body">
                <h6 className="mb-2">Special title treatment</h6>
                <p className="card-text">
                  With supporting text below as a natural lead-in to additional
                  content.
                </p>
              </div>
              <div className="card-footer bg-light">
                <h5>Card Footer</h5>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div className="col-lg-4">
        <div className="card">
          <div className="card-header">
            <h5 className="card-title">Header and footer</h5>
          </div>
          <div className="card-body">
            <div className="card mb-0">
              <div className="card-header bg-light">
                <h5 className="card-title">Card Header</h5>
              </div>
              <div className="card-body">
                <h6 className="mb-2">Special title treatment</h6>
                <p className="card-text">
                  With supporting text below as a natural lead-in to additional
                  content.
                </p>
              </div>
              <div className="card-footer bg-light">
                <h5>Card Footer</h5>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div className="col-lg-6">
        <div className="card">
          <div className="card-header">
            <h5 className="card-title">Horizontal Right Align Content</h5>
          </div>
          <div className="card-body">
            <div className="card mb-0">
              <div className="row g-0">
                <div className="col-md-4">
                  <ImageWithBasePath
                    src="assets/img/media/img-9.jpg"
                    className="img-fluid rounded-start h-100"
                    alt="..."
                  />
                </div>
                <div className="col-md-8">
                  <div className="card-body">
                    <h5 className="card-title">Card title</h5>
                    <p className="card-text mb-1">
                      This is a wider card with supporting text below as a
                      natural lead-in to additional content. This content is a
                      little bit longer.
                    </p>
                    <p className="card-text">
                      <small className="text-muted">
                        Last updated 3 mins ago
                      </small>
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div className="col-lg-6">
        <div className="card">
          <div className="card-header">
            <h5 className="card-title">Horizontal Right Align Content</h5>
          </div>
          <div className="card-body">
            <div className="card mb-0">
              <div className="row g-0">
                <div className="col-md-8">
                  <div className="card-body">
                    <h5 className="card-title">Card title</h5>
                    <p className="card-text mb-1">
                      This is a wider card with supporting text below as a
                      natural lead-in to additional content. This content is a
                      little bit longer.
                    </p>
                    <p className="card-text">
                      <small className="text-muted">
                        Last updated 3 mins ago
                      </small>
                    </p>
                  </div>
                </div>
                <div className="col-md-4">
                  <ImageWithBasePath
                    src="assets/img/media/img-9.jpg"
                    className="img-fluid rounded-end h-100"
                    alt="..."
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div className="col-lg-12">
        <div className="card">
          <div className="card-header">
            <h5 className="card-title">Card styles</h5>
          </div>
          <div className="card-body card-header-border pb-0">
            <div className="row">
              <div className="col-lg-3">
                <div className="card text-white bg-primary">
                  <div className="card-header">
                    <h5 className="card-title text-white">Header</h5>
                  </div>
                  <div className="card-body">
                    <h5 className="card-title text-white">
                      Special title treatment
                    </h5>
                    <p className="card-text">
                      Some quick example text to build on the card title and
                      make up the bulk of the card's content.
                    </p>
                  </div>
                </div>
              </div>
              <div className="col-lg-3">
                <div className="card text-white bg-secondary">
                  <div className="card-header">
                    <h5 className="card-title text-white">Header</h5>
                  </div>
                  <div className="card-body">
                    <h5 className="card-title text-white">
                      Special title treatment
                    </h5>
                    <p className="card-text">
                      Some quick example text to build on the card title and
                      make up the bulk of the card's content.
                    </p>
                  </div>
                </div>
              </div>
              <div className="col-lg-3">
                <div className="card text-white bg-dark">
                  <div className="card-header">
                    <h5 className="card-title text-white">Header</h5>
                  </div>
                  <div className="card-body">
                    <h5 className="card-title text-white">
                      Special title treatment
                    </h5>
                    <p className="card-text">
                      Some quick example text to build on the card title and
                      make up the bulk of the card's content.
                    </p>
                  </div>
                </div>
              </div>
              <div className="col-lg-3">
                <div className="card text-white bg-success">
                  <div className="card-header">
                    <h5 className="card-title text-white">Header</h5>
                  </div>
                  <div className="card-body">
                    <h5 className="card-title text-white">
                      Special title treatment
                    </h5>
                    <p className="card-text">
                      Some quick example text to build on the card title and
                      make up the bulk of the card's content.
                    </p>
                  </div>
                </div>
              </div>
              <div className="col-lg-3">
                <div className="card text-white bg-warning">
                  <div className="card-header">
                    <h5 className="card-title text-white">Header</h5>
                  </div>
                  <div className="card-body">
                    <h5 className="card-title text-white">
                      Special title treatment
                    </h5>
                    <p className="card-text">
                      Some quick example text to build on the card title and
                      make up the bulk of the card's content.
                    </p>
                  </div>
                </div>
              </div>
              <div className="col-lg-3">
                <div className="card text-white bg-danger">
                  <div className="card-header">
                    <h5 className="card-title text-white">Header</h5>
                  </div>
                  <div className="card-body">
                    <h5 className="card-title text-white">
                      Special title treatment
                    </h5>
                    <p className="card-text">
                      Some quick example text to build on the card title and
                      make up the bulk of the card's content.
                    </p>
                  </div>
                </div>
              </div>
              <div className="col-lg-3">
                <div className="card text-white bg-info">
                  <div className="card-header">
                    <h5 className="card-title text-white">Header</h5>
                  </div>
                  <div className="card-body">
                    <h5 className="card-title text-white">
                      Special title treatment
                    </h5>
                    <p className="card-text">
                      Some quick example text to build on the card title and
                      make up the bulk of the card's content.
                    </p>
                  </div>
                </div>
              </div>
              <div className="col-lg-3">
                <div className="card text-white bg-pink">
                  <div className="card-header">
                    <h5 className="card-title text-white">Header</h5>
                  </div>
                  <div className="card-body">
                    <h5 className="card-title text-white">
                      Special title treatment
                    </h5>
                    <p className="card-text">
                      Some quick example text to build on the card title and
                      make up the bulk of the card's content.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div className="col-lg-12">
        <div className="card">
          <div className="card-header">
            <h5 className="card-title">Card Border</h5>
          </div>
          <div className="card-body card-header-border pb-0">
            <div className="row">
              <div className="col-lg-3">
                <div className="card border-primary">
                  <div className="card-header bg-light">
                    <h5 className="card-title">Card Header</h5>
                  </div>
                  <div className="card-body">
                    <h5 className="card-title text-primary">
                      Primary card title
                    </h5>
                    <p className="card-text text-primary">
                      Some quick example text to build on the card title and
                      make up the bulk of the card's content.
                    </p>
                  </div>
                </div>
              </div>
              <div className="col-lg-3">
                <div className="card border-secondary">
                  <div className="card-header bg-light">
                    <h5 className="card-title">Card Header</h5>
                  </div>
                  <div className="card-body">
                    <h5 className="card-title text-secondary">
                      Secondary card title
                    </h5>
                    <p className="card-text text-secondary">
                      Some quick example text to build on the card title and
                      make up the bulk of the card's content.
                    </p>
                  </div>
                </div>
              </div>
              <div className="col-lg-3">
                <div className="card border-success">
                  <div className="card-header bg-light">
                    <h5 className="card-title">Card Header</h5>
                  </div>
                  <div className="card-body">
                    <h5 className="card-title text-success">
                      Success card title
                    </h5>
                    <p className="card-text text-success">
                      Some quick example text to build on the card title and
                      make up the bulk of the card's content.
                    </p>
                  </div>
                </div>
              </div>
              <div className="col-lg-3">
                <div className="card border-danger">
                  <div className="card-header bg-light">
                    <h5 className="card-title">Card Header</h5>
                  </div>
                  <div className="card-body">
                    <h5 className="card-title text-danger">
                      Danger card title
                    </h5>
                    <p className="card-text text-danger">
                      Some quick example text to build on the card title and
                      make up the bulk of the card's content.
                    </p>
                  </div>
                </div>
              </div>
              <div className="col-lg-3">
                <div className="card border-warning">
                  <div className="card-header bg-light">
                    <h5 className="card-title">Card Header</h5>
                  </div>
                  <div className="card-body">
                    <h5 className="card-title text-warning">
                      Warning card title
                    </h5>
                    <p className="card-text text-warning">
                      Some quick example text to build on the card title and
                      make up the bulk of the card's content.
                    </p>
                  </div>
                </div>
              </div>
              <div className="col-lg-3">
                <div className="card border-info">
                  <div className="card-header bg-light">
                    <h5 className="card-title">Card Header</h5>
                  </div>
                  <div className="card-body">
                    <h5 className="card-title text-info">Info card title</h5>
                    <p className="card-text text-info">
                      Some quick example text to build on the card title and
                      make up the bulk of the card's content.
                    </p>
                  </div>
                </div>
              </div>
              <div className="col-lg-3">
                <div className="card">
                  <div className="card-header bg-light">
                    <h5 className="card-title">Card Header</h5>
                  </div>
                  <div className="card-body">
                    <h5 className="card-title">Light card title</h5>
                    <p className="card-text">
                      Some quick example text to build on the card title and
                      make up the bulk of the card's content.
                    </p>
                  </div>
                </div>
              </div>
              <div className="col-lg-3">
                <div className="card border-dark">
                  <div className="card-header bg-light">
                    <h5 className="card-title">Card Header</h5>
                  </div>
                  <div className="card-body">
                    <h5 className="card-title text-dark">Dark card title</h5>
                    <p className="card-text text-dark">
                      Some quick example text to build on the card title and
                      make up the bulk of the card's content.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div className="col-lg-12">
        <div className="card">
          <div className="card-header">
            <h5 className="card-title">Card Groups</h5>
          </div>
          <div className="card-body pb-0 card-header-border">
            <div className="row">
              <div className="col-lg-12">
                <div className="card-group">
                  <div className="card">
                    <ImageWithBasePath
                      src="assets/img/media/img-10.jpg"
                      className="card-img-top"
                      alt="..."
                    />
                    <div className="card-body">
                      <h5 className="card-title">Card title</h5>
                      <p className="card-text">
                        This is a wider card with supporting text below as a
                        natural lead-in to additional content. This content is a
                        little bit longer.
                      </p>
                      <p className="card-text">
                        <small className="text-muted">
                          Last updated 3 mins ago
                        </small>
                      </p>
                    </div>
                  </div>
                  <div className="card">
                    <ImageWithBasePath
                      src="assets/img/media/img-10.jpg"
                      className="card-img-top"
                      alt="..."
                    />
                    <div className="card-body">
                      <h5 className="card-title">Card title</h5>
                      <p className="card-text">
                        This is a wider card with supporting text below as a
                        natural lead-in to additional content. This content is a
                        little bit longer.
                      </p>
                      <p className="card-text">
                        <small className="text-muted">
                          Last updated 3 mins ago
                        </small>
                      </p>
                    </div>
                  </div>
                  <div className="card">
                    <ImageWithBasePath
                      src="assets/img/media/img-10.jpg"
                      className="card-img-top"
                      alt="..."
                    />
                    <div className="card-body">
                      <h5 className="card-title">Card title</h5>
                      <p className="card-text">
                        This is a wider card with supporting text below as a
                        natural lead-in to additional content. This content is a
                        little bit longer.
                      </p>
                      <p className="card-text">
                        <small className="text-muted">
                          Last updated 3 mins ago
                        </small>
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

  );
};

export default Cards;
