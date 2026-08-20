


const Typography = () => {
    return (
      <>
  {/* Page Wrapper */}
  <div className="page-wrapper">
    <div className="content">
      <div className="page-header">
        <div className="page-title">
          <h3>Typography</h3>
        </div>
      </div>
      {/* start row */}
      <div className="row">
        <div className="col-xl-6">
          <div className="card">
            <div className="card-header">
              <h5 className="card-title">Headings Tags</h5>
            </div>
            <div className="card-body">
              <p>
                All HTML headings, <code>&lt;h1&gt;</code> through{" "}
                <code>&lt;h6&gt;</code>, are available.
              </p>
              <h1 className="mb-3">h1. Bootstrap heading</h1>
              <h2 className="mb-3">h2. Bootstrap heading</h2>
              <h3 className="mb-3">h3. Bootstrap heading</h3>
              <h4 className="mb-3">h4. Bootstrap heading</h4>
              <h5 className="mb-3">h5. Bootstrap heading</h5>
              <h6 className="mb-0">h6. Bootstrap heading</h6>
            </div>{" "}
            {/* end card body */}
          </div>{" "}
          {/* end card */}
        </div>{" "}
        {/* end col */}
        <div className="col-xl-6">
          <div className="card">
            <div className="card-header">
              <h5 className="card-title">Heading Class Names</h5>
            </div>
            <div className="card-body">
              <p>
                <code>.h1</code> through <code>.h6</code> classes are also
                available.
              </p>
              <p className="h1 mb-3">h1. Bootstrap heading</p>
              <p className="h2 mb-3">h2. Bootstrap heading</p>
              <p className="h3 mb-3">h3. Bootstrap heading</p>
              <p className="h4 mb-3">h4. Bootstrap heading</p>
              <p className="h5 mb-3">h5. Bootstrap heading</p>
              <p className="h6 mb-0">h6. Bootstrap heading</p>
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
              <h5 className="card-title">Display Headings</h5>
            </div>
            <div className="card-body">
              <p>
                Traditional heading elements are designed to work best in the
                meat of your page content. When you need a heading to stand out,
                consider using a display heading—a larger, slightly more
                opinionated heading style.
              </p>
              <h1 className="display-1">Display 1</h1>
              <h1 className="display-2">Display 2</h1>
              <h1 className="display-3">Display 3</h1>
              <h1 className="display-4">Display 4</h1>
              <h1 className="display-5">Display 5</h1>
              <h1 className="display-6">Display 6</h1>
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
              <h5 className="card-title">Inline Text Elements</h5>
            </div>
            <div className="card-body">
              <p className="text-muted">
                Styling for common inline HTML5 elements.
              </p>
              <p className="lead">Your title goes here</p>
              <p>
                You can use the mark tag to <mark>highlight</mark> text.
              </p>
              <p>
                <del>
                  This line of text is meant to be treated as deleted text.
                </del>
              </p>
              <p>
                <s>
                  This line of text is meant to be treated as no longer
                  accurate.
                </s>
              </p>
              <p>
                <ins>
                  This line of text is meant to be treated as an addition to the
                  document.
                </ins>
              </p>
              <p>
                <u>This line of text will render as underlined</u>
              </p>
              <p>
                <small>
                  This line of text is meant to be treated as fine print.
                </small>
              </p>
              <p>
                <strong>This line rendered as bold text.</strong>
              </p>
              <p>
                <em>This line rendered as italicized text.</em>
              </p>{" "}
              Nulla <abbr title="attribute">attr</abbr> vitae elit libero, a
              pharetra augue.
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
              <h5 className="card-title">Naming a Source</h5>
            </div>
            <div className="card-body pb-0">
              <p className="text-muted">
                A well-known quote, contained in a blockquote element.{" "}
              </p>
              <figure className="mb-0">
                <blockquote className="blockquote">
                  <p>A well-known quote, contained in a blockquote element.</p>
                </blockquote>
                <figcaption className="blockquote-footer">
                  Someone famous in{" "}
                  <cite title="Source Title">Source Title</cite>
                </figcaption>
              </figure>
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
        <div className="col-xl-4">
          <div className="card">
            <div className="card-header">
              <h5 className="card-title">Unordered</h5>
            </div>
            <div className="card-body">
              <p className="text-muted">
                A list of items in which the order does not explicitly matter.
              </p>
              <ul className="mb-0">
                <li>Lorem ipsum dolor sit amet</li>
                <li>Consectetur adipiscing elit</li>
                <li>Integer molestie lorem at massa</li>
                <li>Facilisis in pretium nisl aliquet</li>
                <li>
                  Nulla volutpat aliquam velit
                  <ul>
                    <li>Phasellus iaculis neque</li>
                    <li>Purus sodales ultricies</li>
                    <li>Vestibulum laoreet porttitor sem</li>
                    <li>Ac tristique libero volutpat at</li>
                  </ul>
                </li>
                <li>Faucibus porta lacus fringilla vel</li>
                <li>Aenean sit amet erat nunc</li>
                <li>Eget porttitor lorem</li>
              </ul>
            </div>{" "}
            {/* end card body */}
          </div>{" "}
          {/* end card */}
        </div>{" "}
        {/* end col */}
        <div className="col-xl-4">
          <div className="card">
            <div className="card-header">
              <h5 className="card-title">Ordered</h5>
            </div>
            <div className="card-body">
              <p className="text-muted">
                A list of items in which the order does explicitly matter.
              </p>
              <ol className="mb-0">
                <li>Lorem ipsum dolor sit amet</li>
                <li>Consectetur adipiscing elit</li>
                <li>Integer molestie lorem at massa</li>
                <li>Facilisis in pretium nisl aliquet</li>
                <li>
                  Nulla volutpat aliquam velit
                  <ol>
                    <li>Phasellus iaculis neque</li>
                    <li>Purus sodales ultricies</li>
                    <li>Vestibulum laoreet porttitor sem</li>
                    <li>Ac tristique libero volutpat at</li>
                  </ol>
                </li>
                <li>Faucibus porta lacus fringilla vel</li>
                <li>Aenean sit amet erat nunc</li>
                <li>Eget porttitor lorem</li>
              </ol>
            </div>{" "}
            {/* end card body */}
          </div>{" "}
          {/* end card */}
        </div>{" "}
        {/* end col */}
        <div className="col-xl-4">
          <div className="card">
            <div className="card-header">
              <h5 className="card-title">Unstyled</h5>
            </div>
            <div className="card-body">
              <p className="text-muted">
                <strong>
                  This only applies to immediate children list items
                </strong>
                , meaning you will need to add the class for any nested lists as
                well.
              </p>
              <ul className="list-unstyled">
                <li>Lorem ipsum dolor sit amet</li>
                <li>
                  Integer molestie lorem at massa
                  <ul>
                    <li>Phasellus iaculis neque</li>
                  </ul>
                </li>
                <li>Faucibus porta lacus fringilla vel</li>
                <li>Eget porttitor lorem</li>
              </ul>
              <h5>Inline</h5>
              <p className="text-muted m-b-15">
                Place all list items on a single line with{" "}
                <code>display: inline-block;</code> and some light padding.
              </p>
              <ul className="list-inline mb-0">
                <li className="list-inline-item">Lorem ipsum</li>
                <li className="list-inline-item">Phasellus iaculis</li>
                <li className="list-inline-item">Nulla volutpat</li>
              </ul>
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
        <div className="col-xl-4">
          <div className="card card-h-100">
            <div className="card-header">
              <h5 className="card-title">Abbreviations</h5>
            </div>
            <div className="card-body">
              <p className="text-muted">
                Add .initialism to an abbreviation for a slightly smaller
                font-size.
              </p>
              <p>
                <abbr title="attribute">attr</abbr>
              </p>
              <p>
                <abbr title="HyperText Markup Language" className="initialism">
                  HTML
                </abbr>
              </p>
            </div>{" "}
            {/* end card body */}
          </div>{" "}
          {/* end card */}
        </div>{" "}
        {/* end col */}
        <div className="col-xl-4">
          <div className="card card-h-100">
            <div className="card-header">
              <h5 className="card-title">Alignment</h5>
            </div>
            <div className="card-body">
              <p className="text-muted">
                Use text utilities as needed to change the alignment of your
                blockquote.
              </p>
              <figure className="text-center">
                <blockquote className="blockquote">
                  <p>A well-known quote, contained in a blockquote element.</p>
                </blockquote>
                <figcaption className="blockquote-footer">
                  Someone famous in{" "}
                  <cite title="Source Title">Source Title</cite>
                </figcaption>
              </figure>
              <figure className="text-end mb-0">
                <blockquote className="blockquote">
                  <p>A well-known quote, contained in a blockquote element.</p>
                </blockquote>
                <figcaption className="blockquote-footer mb-0">
                  Someone famous in{" "}
                  <cite title="Source Title">Source Title</cite>
                </figcaption>
              </figure>
            </div>{" "}
            {/* end card body */}
          </div>{" "}
          {/* end card */}
        </div>{" "}
        {/* end col */}
        <div className="col-xl-4">
          <div className="card card-h-100">
            <div className="card-header">
              <h5 className="card-title">Inline</h5>
            </div>
            <div className="card-body">
              <p className="text-muted">
                Remove a list's bullets and apply some light margin with a
                combination of two classes, .list-inline and .list-inline-item.
              </p>
              <ul className="list-inline">
                <li className="list-inline-item">This is a list item.</li>
                <li className="list-inline-item">And another one.</li>
                <li className="list-inline-item">
                  But they're displayed inline.
                </li>
              </ul>
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
          <div className="card card-h-100">
            <div className="card-header">
              <h5 className="card-title">Blockquotes</h5>
            </div>
            <div className="card-body">
              <p className="text-muted">
                For quoting blocks of content from another source within your
                document. Wrap{" "}
                <code>&lt;blockquote class="blockquote"&gt;</code> around any{" "}
                <abbr title="PacesText Markup Language">HTML</abbr> as the
                quote.
              </p>
              <blockquote className="blockquote">
                <p className="mb-0">
                  Lorem ipsum dolor sit amet, consectetur adipiscing elit.
                  Integer posuere erat a ante.
                </p>
              </blockquote>
              <figure>
                {" "}
                <figcaption className="blockquote-footer">
                  Someone famous in{" "}
                  <cite title="Source Title">Source Title</cite>
                </figcaption>
              </figure>
              <p className="text-muted m-b-15">
                Use text utilities as needed to change the alignment of your
                blockquote.
              </p>
              <blockquote className="blockquote text-center">
                <p className="mb-0">
                  Lorem ipsum dolor sit amet, consectetur adipiscing elit.
                  Integer posuere erat a ante.
                </p>
              </blockquote>
              <figure>
                <figcaption className="blockquote-footer text-center">
                  Someone famous in{" "}
                  <cite title="Source Title">Source Title</cite>
                </figcaption>
              </figure>
              <blockquote className="blockquote text-end">
                <p className="mb-0">
                  Lorem ipsum dolor sit amet, consectetur adipiscing elit.
                  Integer posuere erat a ante.
                </p>
              </blockquote>
              <figure className="mb-0">
                <figcaption className="blockquote-footer text-end mb-0">
                  Someone famous in{" "}
                  <cite title="Source Title">Source Title</cite>
                </figcaption>
              </figure>
            </div>{" "}
            {/* end card body */}
          </div>{" "}
          {/* end card */}
        </div>{" "}
        {/* end col */}
        <div className="col-xl-6">
          <div className="card card-h-100">
            <div className="card-header">
              <h5 className="card-title">Description List Alignment</h5>
            </div>
            <div className="card-body">
              <p className="text-muted">
                Align terms and descriptions horizontally by using our grid
                system’s predefined classes (or semantic mixins). For longer
                terms, you can optionally add a <code>.text-truncate</code>{" "}
                class to truncate the text with an ellipsis.
              </p>
              <dl className="row mb-0">
                <dt className="col-sm-3">Description lists</dt>
                <dd className="col-sm-9">
                  A description list is perfect for defining terms.
                </dd>
                <dt className="col-sm-3">Euismod</dt>
                <dd className="col-sm-9">
                  <p>
                    Vestibulum id ligula porta felis euismod semper eget lacinia
                    odio sem nec elit.
                  </p>
                  <p>Donec id elit non mi porta gravida at eget metus.</p>
                </dd>
                <dt className="col-sm-3">Malesuada porta</dt>
                <dd className="col-sm-9">
                  Etiam porta sem malesuada magna mollis euismod.
                </dd>
                <dt className="col-sm-3 text-truncate">
                  Truncated term is truncated
                </dt>
                <dd className="col-sm-9">
                  Fusce dapibus, tellus ac cursus commodo, tortor mauris
                  condimentum nibh, ut fermentum massa justo sit amet risus.
                </dd>
                <dt className="col-sm-3">Nesting</dt>
                <dd className="col-sm-9 mb-0">
                  <dl className="row mb-0">
                    <dt className="col-sm-4">Nested definition list</dt>
                    <dd className="col-sm-8 mb-0">
                      Aenean posuere, tortor sed cursus feugiat, nunc augue
                      blandit nunc.
                    </dd>
                  </dl>
                </dd>
              </dl>
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

export default Typography
