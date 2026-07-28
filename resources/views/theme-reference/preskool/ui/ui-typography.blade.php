<?php $page = 'ui-typography'; ?>
@extends('layout.mainlayout')
@section('content')
   <!-- Page Wrapper -->			
   <div class="page-wrapper">
    <div class="content">
        @component('components.breadcrumb')
        @slot('title')
      Typography
        @endslot
    @endcomponent	
        <!-- start row -->
        <div class="row">

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Headings Tags</h5>
                    </div>
                    <div class="card-body">
                        <p>All HTML headings, <code>&lt;h1&gt;</code> through <code>&lt;h6&gt;</code>, are available.</p>
                        <h1 class="mb-3">h1. Bootstrap heading</h1>
                        <h2 class="mb-3">h2. Bootstrap heading</h2>
                        <h3 class="mb-3">h3. Bootstrap heading</h3>
                        <h4 class="mb-3">h4. Bootstrap heading</h4>
                        <h5 class="mb-3">h5. Bootstrap heading</h5>
                        <h6 class="mb-0">h6. Bootstrap heading</h6>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Heading Class Names</h5>
                    </div>
                    <div class="card-body">
                    <p><code>.h1</code> through <code>.h6</code> classes are also available.</p>
                        <p class="h1 mb-3">h1. Bootstrap heading</p>
                        <p class="h2 mb-3">h2. Bootstrap heading</p>
                        <p class="h3 mb-3">h3. Bootstrap heading</p>
                        <p class="h4 mb-3">h4. Bootstrap heading</p>
                        <p class="h5 mb-3">h5. Bootstrap heading</p>
                        <p class="h6 mb-0">h6. Bootstrap heading</p>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div>
        <!-- end row -->

        <!-- start row -->
        <div class="row">

            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Display Headings</h5>
                    </div>
                    <div class="card-body">
                        <p>Traditional heading elements are designed to work best in the meat of your page content. When you need a heading to stand out, consider using a display heading—a larger, slightly more opinionated heading style.</p>

                        <h1 class="display-1">Display 1</h1>
                        <h1 class="display-2">Display 2</h1>
                        <h1 class="display-3">Display 3</h1>
                        <h1 class="display-4">Display 4</h1>
                        <h1 class="display-5">Display 5</h1>
                        <h1 class="display-6">Display 6</h1>

                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div>
        <!-- end row -->

        <!-- start row -->
        <div class="row">

            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Inline Text Elements</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Styling for common inline HTML5 elements.</p>
                        <p class="lead">Your title goes here</p>
                        <p>You can use the mark tag to <mark>highlight</mark> text.</p>
                        <p><del>This line of text is meant to be treated as deleted text.</del></p>
                        <p><s>This line of text is meant to be treated as no longer accurate.</s></p>
                        <p><ins>This line of text is meant to be treated as an addition to the document.</ins></p>
                        <p><u>This line of text will render as underlined</u></p>
                        <p><small>This line of text is meant to be treated as fine print.</small></p>
                        <p><strong>This line rendered as bold text.</strong></p>
                        <p><em>This line rendered as italicized text.</em></p> Nulla <abbr title="attribute">attr</abbr> vitae elit libero, a pharetra augue.

                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div>
        <!-- end row -->

        <!-- start row -->
        <div class="row">

            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Naming a Source</h5>
                    </div>
                    <div class="card-body pb-0">
                        <p class="text-muted">A well-known quote, contained in a blockquote element. </p>
                        <figure class="mb-0">
                            <blockquote class="blockquote">
                                <p>A well-known quote, contained in a blockquote element.</p>
                            </blockquote>
                            <figcaption class="blockquote-footer">
                                Someone famous in <cite title="Source Title">Source Title</cite>
                            </figcaption>
                        </figure>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div> 
        <!-- end row -->

        <!-- start row -->
        <div class="row">

            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Unordered</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">A list of items in which the order does not explicitly matter.</p>
                        <ul class="mb-0">
                            <li>
                                Lorem ipsum dolor sit amet
                            </li>
                            <li>
                                Consectetur adipiscing elit
                            </li>
                            <li>
                                Integer molestie lorem at massa
                            </li>
                            <li>
                                Facilisis in pretium nisl aliquet
                            </li>
                            <li>
                                Nulla volutpat aliquam velit
                                <ul>
                                    <li>
                                        Phasellus iaculis neque
                                    </li>
                                    <li>
                                        Purus sodales ultricies
                                    </li>
                                    <li>
                                        Vestibulum laoreet porttitor sem
                                    </li>
                                    <li>
                                        Ac tristique libero volutpat at
                                    </li>
                                </ul>
                            </li>
                            <li>
                                Faucibus porta lacus fringilla vel
                            </li>
                            <li>
                                Aenean sit amet erat nunc
                            </li>
                            <li>
                                Eget porttitor lorem
                            </li>
                        </ul>

                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Ordered</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">A list of items in which the order does explicitly matter.</p>
                        <ol class="mb-0">
                            <li>
                                Lorem ipsum dolor sit amet
                            </li>
                            <li>
                                Consectetur adipiscing elit
                            </li>
                            <li>
                                Integer molestie lorem at massa
                            </li>
                            <li>
                                Facilisis in pretium nisl aliquet
                            </li>
                            <li>
                                Nulla volutpat aliquam velit
                                <ol>
                                    <li>
                                        Phasellus iaculis neque
                                    </li>
                                    <li>
                                        Purus sodales ultricies
                                    </li>
                                    <li>
                                        Vestibulum laoreet porttitor sem
                                    </li>
                                    <li>
                                        Ac tristique libero volutpat at
                                    </li>
                                </ol>
                            </li>
                            <li>
                                Faucibus porta lacus fringilla vel
                            </li>
                            <li>
                                Aenean sit amet erat nunc
                            </li>
                            <li>
                                Eget porttitor lorem
                            </li>
                        </ol>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Unstyled</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted"><strong>This only applies to immediate children list items</strong>, meaning you will need to add the class for any nested lists as well.</p>
                        <ul class="list-unstyled">
                            <li>
                                Lorem ipsum dolor sit amet
                            </li>
                            <li>
                                Integer molestie lorem at massa
                                <ul>
                                    <li>
                                        Phasellus iaculis neque
                                    </li>
                                </ul>
                            </li>
                            <li>
                                Faucibus porta lacus fringilla vel
                            </li>
                            <li>
                                Eget porttitor lorem
                            </li>
                        </ul>

                        <h5>Inline</h5>
                        <p class="text-muted m-b-15">Place all list items on a single line with <code>display: inline-block;</code> and some light padding.</p>

                        <ul class="list-inline mb-0">
                            <li class="list-inline-item">
                                Lorem ipsum
                            </li>
                            <li class="list-inline-item">
                                Phasellus iaculis
                            </li>
                            <li class="list-inline-item">
                                Nulla volutpat
                            </li>
                        </ul>

                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div>
        <!-- end row -->

        <!-- start row -->
        <div class="row">

            <div class="col-xl-4">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h5 class="card-title">Abbreviations</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Add .initialism to an abbreviation for a slightly smaller font-size.</p>
                        <p><abbr title="attribute">attr</abbr></p>
                        <p><abbr title="HyperText Markup Language" class="initialism">HTML</abbr></p>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-4">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h5 class="card-title">Alignment</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Use text utilities as needed to change the alignment of your
                            blockquote.</p>
                        <figure class="text-center">
                            <blockquote class="blockquote">
                                <p>A well-known quote, contained in a blockquote element.</p>
                            </blockquote>
                            <figcaption class="blockquote-footer">
                                Someone famous in <cite title="Source Title">Source Title</cite>
                            </figcaption>
                        </figure>
                        <figure class="text-end mb-0">
                            <blockquote class="blockquote">
                                <p>A well-known quote, contained in a blockquote element.</p>
                            </blockquote>
                            <figcaption class="blockquote-footer mb-0">
                                Someone famous in <cite title="Source Title">Source Title</cite>
                            </figcaption>
                        </figure>

                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-4">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h5 class="card-title">Inline</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Remove a list's bullets and apply some light margin with a combination of two classes, .list-inline and .list-inline-item.</p>
                        <ul class="list-inline">
                            <li class="list-inline-item">This is a list item.</li>
                            <li class="list-inline-item">And another one.</li>
                            <li class="list-inline-item">But they're displayed inline.</li>
                        </ul>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div>
        <!-- end row -->

        <!-- start row -->
        <div class="row">

            <div class="col-xl-6">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h5 class="card-title">Blockquotes</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">For quoting blocks of content from another source within your document. Wrap <code>&lt;blockquote class="blockquote"&gt;</code> around any <abbr title="PacesText Markup Language">HTML</abbr> as the quote.</p>
                        <blockquote class="blockquote">
                            <p class="mb-0">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                        </blockquote>
                        <figure> <figcaption class="blockquote-footer">Someone famous in <cite title="Source Title">Source Title</cite></figcaption></figure>
                        <p class="text-muted m-b-15">Use text utilities as needed to change the alignment of your blockquote.</p>
                        <blockquote class="blockquote text-center">
                            <p class="mb-0">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                        </blockquote>
                        <figure><figcaption class="blockquote-footer text-center">Someone famous in <cite title="Source Title">Source Title</cite></figcaption></figure>
                        <blockquote class="blockquote text-end">
                            <p class="mb-0">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                        </blockquote>
                        <figure class="mb-0"><figcaption class="blockquote-footer text-end mb-0">Someone famous in <cite title="Source Title">Source Title</cite></figcaption></figure>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h5 class="card-title">Description List Alignment</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Align terms and descriptions horizontally by using our grid system’s predefined classes (or semantic mixins). For longer terms, you can optionally add a <code>.text-truncate</code> class to truncate the text with an ellipsis.</p>
                        <dl class="row mb-0">
                            <dt class="col-sm-3">Description lists</dt>
                            <dd class="col-sm-9">A description list is perfect for defining terms.</dd>

                            <dt class="col-sm-3">Euismod</dt>
                            <dd class="col-sm-9">
                                <p>Vestibulum id ligula porta felis euismod semper eget lacinia odio sem nec elit.</p>
                                <p>Donec id elit non mi porta gravida at eget metus.</p>
                            </dd>
                            <dt class="col-sm-3">Malesuada porta</dt>
                            <dd class="col-sm-9">Etiam porta sem malesuada magna mollis euismod.</dd>
                            <dt class="col-sm-3 text-truncate">Truncated term is truncated</dt>
                            <dd class="col-sm-9">Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus.</dd>
                            <dt class="col-sm-3">Nesting</dt>
                            <dd class="col-sm-9 mb-0">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Nested definition list</dt>
                                    <dd class="col-sm-8 mb-0">Aenean posuere, tortor sed cursus feugiat, nunc augue blandit nunc.</dd>
                                </dl>
                            </dd>
                        </dl>

                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->
            
        </div>
        <!-- end row -->
    </div>
</div>
<!-- /Page Wrapper -->
@endsection
