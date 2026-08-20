

const Accordion = () => {
    return (
     <div className="page-wrapper">
  <div className="content">
    <div className="page-header">
      <div className="page-title">
        <h3>Accordion</h3>
      </div>
    </div>
    {/* start row */}
    <div className="row">
      <div className="col-xl-6">
        <div className="card">
          <div className="card-header">
            <h5 className="card-title">Default Accordion</h5>
          </div>
          <div className="card-body">
            <div className="accordion" id="accordionExample">
              <div className="accordion-item">
                <h2 className="accordion-header">
                  <button
                    className="accordion-button"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#collapseOne"
                    aria-expanded="true"
                    aria-controls="collapseOne"
                  >
                    Accordion Item #1
                  </button>
                </h2>
                <div
                  id="collapseOne"
                  className="accordion-collapse collapse show"
                  data-bs-parent="#accordionExample"
                >
                  <div className="accordion-body">
                    <strong>This is the first item’s accordion body.</strong> It
                    is shown by default, until the collapse plugin adds the
                    appropriate classes that we use to style each element. These
                    classes control the overall appearance, as well as the
                    showing and hiding via CSS transitions. You can modify any
                    of this with custom CSS or overriding our default variables.
                    It’s also worth noting that just about any HTML can go
                    within the <code>.accordion-body</code>, though the
                    transition does limit overflow.
                  </div>
                </div>
              </div>
              <div className="accordion-item">
                <h2 className="accordion-header">
                  <button
                    className="accordion-button collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#collapseTwo"
                    aria-expanded="false"
                    aria-controls="collapseTwo"
                  >
                    Accordion Item #2
                  </button>
                </h2>
                <div
                  id="collapseTwo"
                  className="accordion-collapse collapse"
                  data-bs-parent="#accordionExample"
                >
                  <div className="accordion-body">
                    <strong>This is the second item’s accordion body.</strong>{" "}
                    It is hidden by default, until the collapse plugin adds the
                    appropriate classes that we use to style each element. These
                    classes control the overall appearance, as well as the
                    showing and hiding via CSS transitions. You can modify any
                    of this with custom CSS or overriding our default variables.
                    It’s also worth noting that just about any HTML can go
                    within the <code>.accordion-body</code>, though the
                    transition does limit overflow.
                  </div>
                </div>
              </div>
              <div className="accordion-item">
                <h2 className="accordion-header">
                  <button
                    className="accordion-button collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#collapseThree"
                    aria-expanded="false"
                    aria-controls="collapseThree"
                  >
                    Accordion Item #3
                  </button>
                </h2>
                <div
                  id="collapseThree"
                  className="accordion-collapse collapse"
                  data-bs-parent="#accordionExample"
                >
                  <div className="accordion-body">
                    <strong>This is the third item’s accordion body.</strong> It
                    is hidden by default, until the collapse plugin adds the
                    appropriate classes that we use to style each element. These
                    classes control the overall appearance, as well as the
                    showing and hiding via CSS transitions. You can modify any
                    of this with custom CSS or overriding our default variables.
                    It’s also worth noting that just about any HTML can go
                    within the <code>.accordion-body</code>, though the
                    transition does limit overflow.
                  </div>
                </div>
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
            <h5 className="card-title">Flush Accordions</h5>
          </div>
          <div className="card-body">
            <p className="text-muted">
              Add <code>.accordion-flush</code> to remove some borders and
              rounded corners to render accordions edge-to-edge with their
              parent container.
            </p>
            <div
              className="accordion accordion-flush"
              id="accordionFlushExample"
            >
              <div className="accordion-item">
                <h2 className="accordion-header">
                  <button
                    className="accordion-button"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#flush-collapseOne"
                    aria-expanded="false"
                    aria-controls="flush-collapseOne"
                  >
                    Accordion Item #1
                  </button>
                </h2>
                <div
                  id="flush-collapseOne"
                  className="accordion-collapse collapse show"
                  data-bs-parent="#accordionFlushExample"
                >
                  <div className="accordion-body">
                    Placeholder content for this accordion, which is intended to
                    demonstrate the <code>.accordion-flush</code> class. This is
                    the first item’s accordion body.
                  </div>
                </div>
              </div>
              <div className="accordion-item">
                <h2 className="accordion-header">
                  <button
                    className="accordion-button collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#flush-collapseTwo"
                    aria-expanded="false"
                    aria-controls="flush-collapseTwo"
                  >
                    Accordion Item #2
                  </button>
                </h2>
                <div
                  id="flush-collapseTwo"
                  className="accordion-collapse collapse"
                  data-bs-parent="#accordionFlushExample"
                >
                  <div className="accordion-body">
                    Placeholder content for this accordion, which is intended to
                    demonstrate the <code>.accordion-flush</code> class. This is
                    the second item’s accordion body. Let’s imagine this being
                    filled with some actual content.
                  </div>
                </div>
              </div>
              <div className="accordion-item">
                <h2 className="accordion-header">
                  <button
                    className="accordion-button collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#flush-collapseThree"
                    aria-expanded="false"
                    aria-controls="flush-collapseThree"
                  >
                    Accordion Item #3
                  </button>
                </h2>
                <div
                  id="flush-collapseThree"
                  className="accordion-collapse collapse"
                  data-bs-parent="#accordionFlushExample"
                >
                  <div className="accordion-body">
                    Placeholder content for this accordion, which is intended to
                    demonstrate the <code>.accordion-flush</code> class. This is
                    the third item’s accordion body. Nothing more exciting
                    happening here in terms of content, but just filling up the
                    space to make it look, at least at first glance, a bit more
                    representative of how this would look in a real-world
                    application.
                  </div>
                </div>
              </div>
            </div>
          </div>{" "}
          {/* end card body*/}
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
            <h5 className="card-title">Bordered Accordions</h5>
          </div>
          <div className="card-body">
            <p className="text-muted">
              Using the card component, you can extend the default collapse
              behavior to create an accordion. To properly achieve the accordion
              style, be sure to use <code>.accordion</code> as a wrapper.
            </p>
            <div
              className="accordion accordion-bordered"
              id="BorderedaccordionExample"
            >
              <div className="accordion-item">
                <h2 className="accordion-header" id="BorderedheadingOne">
                  <button
                    className="accordion-button"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#BorderedcollapseOne"
                    aria-expanded="true"
                    aria-controls="BorderedcollapseOne"
                  >
                    Accordion Item #1
                  </button>
                </h2>
                <div
                  id="BorderedcollapseOne"
                  className="accordion-collapse collapse show"
                  aria-labelledby="BorderedheadingOne"
                  data-bs-parent="#BorderedaccordionExample"
                >
                  <div className="accordion-body">
                    <strong>This is the first item's accordion body.</strong> It
                    is shown by default, until the collapse plugin adds the
                    appropriate classes that we use to style each element. These
                    classes control the overall appearance, as well as the
                    showing and hiding via CSS transitions. You can modify any
                    of this with custom CSS or overriding our default variables.
                    It's also worth noting that just about any HTML can go
                    within the <code>.accordion-body</code>, though the
                    transition does limit overflow.
                  </div>
                </div>
              </div>
              <div className="accordion-item">
                <h2 className="accordion-header" id="BorderedheadingTwo">
                  <button
                    className="accordion-button collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#BorderedcollapseTwo"
                    aria-expanded="false"
                    aria-controls="BorderedcollapseTwo"
                  >
                    Accordion Item #2
                  </button>
                </h2>
                <div
                  id="BorderedcollapseTwo"
                  className="accordion-collapse collapse"
                  aria-labelledby="BorderedheadingTwo"
                  data-bs-parent="#BorderedaccordionExample"
                >
                  <div className="accordion-body">
                    <strong>This is the second item's accordion body.</strong>{" "}
                    It is hidden by default, until the collapse plugin adds the
                    appropriate classes that we use to style each element. These
                    classes control the overall appearance, as well as the
                    showing and hiding via CSS transitions. You can modify any
                    of this with custom CSS or overriding our default variables.
                    It's also worth noting that just about any HTML can go
                    within the <code>.accordion-body</code>, though the
                    transition does limit overflow.
                  </div>
                </div>
              </div>
              <div className="accordion-item">
                <h2 className="accordion-header" id="BorderedheadingThree">
                  <button
                    className="accordion-button collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#BorderedcollapseThree"
                    aria-expanded="false"
                    aria-controls="BorderedcollapseThree"
                  >
                    Accordion Item #3
                  </button>
                </h2>
                <div
                  id="BorderedcollapseThree"
                  className="accordion-collapse collapse"
                  aria-labelledby="BorderedheadingThree"
                  data-bs-parent="#BorderedaccordionExample"
                >
                  <div className="accordion-body">
                    <strong>This is the third item's accordion body.</strong> It
                    is hidden by default, until the collapse plugin adds the
                    appropriate classes that we use to style each element. These
                    classes control the overall appearance, as well as the
                    showing and hiding via CSS transitions. You can modify any
                    of this with custom CSS or overriding our default variables.
                    It's also worth noting that just about any HTML can go
                    within the <code>.accordion-body</code>, though the
                    transition does limit overflow.
                  </div>
                </div>
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
            <h5 className="card-title">Accordion Without Arrow</h5>
          </div>
          <div className="card-body">
            <p className="text-muted">
              Using the card component, you can extend the default collapse
              behavior to create an accordion. To properly achieve the accordion
              style, be sure to use <code>.accordion</code> as a wrapper.{" "}
            </p>
            <div
              className="accordion accordion-arrow-none"
              id="withoutarrowaccordionExample"
            >
              <div className="accordion-item">
                <h2 className="accordion-header" id="withoutarrowheadingOne">
                  <button
                    className="accordion-button"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#withoutarrowcollapseOne"
                    aria-expanded="true"
                    aria-controls="withoutarrowcollapseOne"
                  >
                    Accordion Item #1
                  </button>
                </h2>
                <div
                  id="withoutarrowcollapseOne"
                  className="accordion-collapse collapse show"
                  aria-labelledby="withoutarrowheadingOne"
                  data-bs-parent="#withoutarrowaccordionExample"
                >
                  <div className="accordion-body">
                    <strong>This is the first item's accordion body.</strong> It
                    is shown by default, until the collapse plugin adds the
                    appropriate classes that we use to style each element. These
                    classes control the overall appearance, as well as the
                    showing and hiding via CSS transitions. You can modify any
                    of this with custom CSS or overriding our default variables.
                    It's also worth noting that just about any HTML can go
                    within the <code>.accordion-body</code>, though the
                    transition does limit overflow.
                  </div>
                </div>
              </div>
              <div className="accordion-item">
                <h2 className="accordion-header" id="withoutarrowheadingTwo">
                  <button
                    className="accordion-button collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#withoutarrowcollapseTwo"
                    aria-expanded="false"
                    aria-controls="withoutarrowcollapseTwo"
                  >
                    Accordion Item #2
                  </button>
                </h2>
                <div
                  id="withoutarrowcollapseTwo"
                  className="accordion-collapse collapse"
                  aria-labelledby="withoutarrowheadingTwo"
                  data-bs-parent="#withoutarrowaccordionExample"
                >
                  <div className="accordion-body">
                    <strong>This is the second item's accordion body.</strong>{" "}
                    It is shown by default, until the collapse plugin adds the
                    appropriate classes that we use to style each element. These
                    classes control the overall appearance, as well as the
                    showing and hiding via CSS transitions. You can modify any
                    of this with custom CSS or overriding our default variables.
                    It's also worth noting that just about any HTML can go
                    within the <code>.accordion-body</code>, though the
                    transition does limit overflow.
                  </div>
                </div>
              </div>
              <div className="accordion-item">
                <h2 className="accordion-header" id="withoutarrowheadingThree">
                  <button
                    className="accordion-button collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#withoutarrowcollapseThree"
                    aria-expanded="false"
                    aria-controls="withoutarrowcollapseThree"
                  >
                    Accordion Item #3
                  </button>
                </h2>
                <div
                  id="withoutarrowcollapseThree"
                  className="accordion-collapse collapse"
                  aria-labelledby="withoutarrowheadingThree"
                  data-bs-parent="#withoutarrowaccordionExample"
                >
                  <div className="accordion-body">
                    <strong>This is the third item's accordion body.</strong> It
                    is shown by default, until the collapse plugin adds the
                    appropriate classes that we use to style each element. These
                    classes control the overall appearance, as well as the
                    showing and hiding via CSS transitions. You can modify any
                    of this with custom CSS or overriding our default variables.
                    It's also worth noting that just about any HTML can go
                    within the <code>.accordion-body</code>, though the
                    transition does limit overflow.
                  </div>
                </div>
              </div>
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

export default Accordion
