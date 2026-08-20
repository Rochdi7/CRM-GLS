<?php $page = 'ui-pagination'; ?>
@extends('layout.mainlayout')
@section('content')
<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content">
        <div class="page-header">
            <h4 class="page-title my-auto">Pagination</h4>
            <div>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                    <a href="javascript:void(0)">Ui Elements</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Pagination</li>
                </ol>
            </div>
        </div>
        <!-- start row -->
        <div class="row">

            <div class="col-xl-6">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h5 class="card-title">Default Pagination</h5>
                    </div>
                    <div class="card-body">
                        <p>Looking to use an icon or symbol in place of text for some pagination links? Be sure
                            to provide proper screen reader support with <code>aria</code> attributes.</p>

                        <nav aria-label="Page navigation example">
                            <ul class="pagination">
                                <li class="page-item">
                                    <a class="page-link" href="#" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                <li class="page-item"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="#" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Disabled and Active States</h5>
                    </div>
                    <div class="card-body">
                        <p>Add .disabled to a .page-item to make it appear un-clickable. While .disabled uses
                            pointer-events: none to disable
                            <code>.disabled</code> for links that appear un-clickable and <code>.active</code>
                            to indicate the current page.
                        </p>
                        <nav aria-label="...">
                            <ul class="pagination">
                                <li class="page-item disabled">
                                    <a class="page-link">Previous</a>
                                </li>
                                <li class="page-item"><a class="page-link" href="#">1</a></li>
                                <li class="page-item active">
                                    <a class="page-link" href="#" aria-current="page">2</a>
                                </li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="#">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div>
        <!-- end row -->

        <!-- start row -->
        <div class="row">

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Sizing</h5>
                    </div>
                    <div class="card-body">
                        <p>Fancy larger or smaller pagination? Add <code>.pagination-lg</code> or
                            <code>.pagination-sm</code> for additional sizes.</p>

                        <nav aria-label="...">
                            <ul class="pagination pagination-lg mb-3">
                                <li class="page-item active">
                                    <a class="page-link" aria-current="page">1</a>
                                </li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                            </ul>
                        </nav>

                        <nav aria-label="...">
                            <ul class="pagination pagination-sm">
                                <li class="page-item active">
                                    <a class="page-link" aria-current="page">1</a>
                                </li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                            </ul>
                        </nav>

                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Alignment</h5>
                    </div>
                    <div class="card-body">
                        <p>Change the alignment of pagination components with flexbox utilities. For example,
                            with <code>.justify-content-center</code>: or <code>.justify-content-end</code></p>

                        <div class="mb-3">
                            <nav aria-label="Page navigation example mb-3">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item disabled">
                                        <a class="page-link">Previous</a>
                                    </li>
                                    <li class="page-item"><a class="page-link" href="#">1</a></li>
                                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                                    <li class="page-item">
                                        <a class="page-link" href="#">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>

                        <nav aria-label="Page navigation example">
                            <ul class="pagination justify-content-end">
                                <li class="page-item disabled">
                                    <a class="page-link">Previous</a>
                                </li>
                                <li class="page-item"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="#">Next</a>
                                </li>
                            </ul>
                        </nav>

                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div>
        <!-- end row -->

        <!-- start row -->
        <div class="row">

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Custom Icon Pagination</h5>
                    </div>
                    <div class="card-body">
                        <p>Add <code> .pagination-boxed</code> for rounded pagination.</p>

                        <nav>
                            <ul class="pagination pagination-boxed flex-wrap gap-lg-0 gap-md-0 gap-1 mb-3">
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0);" aria-label="Previous">
                                        <i class="ti ti-chevron-left"></i>
                                    </a>
                                </li>
                                <li class="page-item active"><a class="page-link" href="javascript:void(0);">1</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">2</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">3</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">4</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">5</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0);" aria-label="Next">
                                        <i class="ti ti-chevron-right align-middle"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>

                        <nav>
                            <ul class="pagination pagination-boxed flex-wrap gap-lg-0 gap-md-0 gap-1 mb-3">
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0);" aria-label="Previous">
                                        <i class="ti ti-arrow-left"></i>
                                    </a>
                                </li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">1</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">2</a></li>
                                <li class="page-item active"><a class="page-link" href="javascript:void(0);">3</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">4</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">5</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0);" aria-label="Next">
                                        <i class="ti ti-arrow-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>

                        <nav>
                            <ul class="pagination pagination-boxed mb-0 flex-wrap gap-lg-0 gap-md-0 gap-1">
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0);" aria-label="Previous">
                                        <i class="ti ti-chevron-left-pipe align-middle"></i>
                                    </a>
                                </li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">1</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">2</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">3</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">4</a></li>
                                <li class="page-item active"><a class="page-link" href="javascript:void(0);">5</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0);" aria-label="Next">
                                        <i class="ti ti-chevron-right-pipe align-middle"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>

                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Boxed Pagination</h5>
                    </div>
                    <div class="card-body">
                        <p>Add <code> .pagination-boxed</code> for rounded pagination.</p>

                        <nav>
                            <ul class="pagination pagination-sm pagination-boxed flex-wrap gap-lg-0 gap-md-0 gap-1 mb-3">
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0);" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">1</a></li>
                                <li class="page-item active"><a class="page-link" href="javascript:void(0);">2</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">3</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">4</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">5</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0);" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>

                            <ul class="pagination pagination-boxed flex-wrap gap-lg-0 gap-md-0 gap-1 mb-3">
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0);" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">1</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">2</a></li>
                                <li class="page-item active"><a class="page-link" href="javascript:void(0);">3</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">4</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">5</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0);" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>

                            <ul class="pagination pagination-lg pagination-boxed mb-0 flex-wrap gap-lg-0 gap-md-0 gap-1">
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0);" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">1</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">2</a></li>
                                <li class="page-item active"><a class="page-link" href="javascript:void(0);">3</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">4</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">5</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0);" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div>
        <!-- end row -->

        <!-- start row  -->
        <div class="row">

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Rounded Pagination</h5>
                    </div>
                    <div class="card-body">
                        <p>Add <code> .pagination-rounded</code> for rounded pagination.</p>

                        <nav>
                            <ul class="pagination pagination-rounded pagination-boxed mb-0 flex-wrap gap-lg-0 gap-md-0 gap-1">
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0);" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">1</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">2</a></li>
                                <li class="page-item active"><a class="page-link" href="javascript:void(0);">3</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">4</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">5</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0);" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>

                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Soft Pagination</h5>
                    </div>
                    <div class="card-body">
                        <p>Add <code> .pagination-rounded</code> for rounded pagination.</p>

                        <nav>
                            <ul class="pagination pagination-soft-primary pagination-boxed mb-0 flex-wrap gap-lg-0 gap-md-0 gap-1">
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0);" aria-label="Previous">
                                        <i class="ti ti-chevron-left"></i>
                                    </a>
                                </li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">1</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">2</a></li>
                                <li class="page-item active"><a class="page-link" href="javascript:void(0);">3</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">4</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">5</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0);" aria-label="Next">
                                        <i class="ti ti-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div>
        <!-- end row -->

        <!-- start row -->
        <div class="row">

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Custom Color Pagination</h5>
                    </div>
                    <div class="card-body">
                        <p>Add <code> .pagination-boxed</code> for rounded pagination.</p>
                        <nav>
                            <ul class="pagination pagination-boxed pagination-primary flex-wrap gap-lg-0 gap-md-0 gap-1 mb-3">
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0);" aria-label="Previous">
                                        <i class="ti ti-chevron-left"></i>
                                    </a>
                                </li>
                                <li class="page-item active"><a class="page-link" href="javascript:void(0);">1</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">2</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">3</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">4</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">5</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0);" aria-label="Next">
                                        <i class="ti ti-chevron-right align-middle"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>

                        <nav>
                            <ul class="pagination pagination-boxed pagination-secondary flex-wrap gap-lg-0 gap-md-0 gap-1 mb-3">
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0);" aria-label="Previous">
                                        <i class="ti ti-arrow-left"></i>
                                    </a>
                                </li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">1</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">2</a></li>
                                <li class="page-item active"><a class="page-link" href="javascript:void(0);">3</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">4</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">5</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0);" aria-label="Next">
                                        <i class="ti ti-arrow-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>

                        <nav>
                            <ul class="pagination pagination-boxed pagination-dark mb-0 flex-wrap gap-lg-0 gap-md-0 gap-1">
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0);" aria-label="Previous">
                                        <i class="ti ti-chevron-left-pipe align-middle"></i>
                                    </a>
                                </li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">1</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">2</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">3</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">4</a></li>
                                <li class="page-item active"><a class="page-link" href="javascript:void(0);">5</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0);" aria-label="Next">
                                        <i class="ti ti-chevron-right-pipe align-middle"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Gradient Color Pagination</h5>
                    </div>
                    <div class="card-body">
                        <p>Add <code> .pagination-boxed</code> for rounded pagination.</p>

                        <nav>
                            <ul class="pagination pagination-boxed pagination-gradient pagination-primary flex-wrap gap-lg-0 gap-md-0 gap-1 mb-3">
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0);" aria-label="Previous">
                                        <i class="ti ti-chevron-left"></i>
                                    </a>
                                </li>
                                <li class="page-item active"><a class="page-link" href="javascript:void(0);">1</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">2</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">3</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">4</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">5</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0);" aria-label="Next">
                                        <i class="ti ti-chevron-right align-middle"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>

                        <nav>
                            <ul class="pagination pagination-boxed pagination-secondary pagination-gradient flex-wrap gap-lg-0 gap-md-0 gap-1 mb-3">
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0);" aria-label="Previous">
                                        <i class="ti ti-arrow-left"></i>
                                    </a>
                                </li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">1</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">2</a></li>
                                <li class="page-item active"><a class="page-link" href="javascript:void(0);">3</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">4</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">5</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0);" aria-label="Next">
                                        <i class="ti ti-arrow-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>

                        <nav>
                            <ul class="pagination pagination-boxed pagination-dark pagination-gradient mb-0 flex-wrap gap-lg-0 gap-md-0 gap-1">
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0);" aria-label="Previous">
                                        <i class="ti ti-chevron-left-pipe align-middle"></i>
                                    </a>
                                </li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">1</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">2</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">3</a></li>
                                <li class="page-item"><a class="page-link" href="javascript:void(0);">4</a></li>
                                <li class="page-item active"><a class="page-link" href="javascript:void(0);">5</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0);" aria-label="Next">
                                        <i class="ti ti-chevron-right-pipe align-middle"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div>
        <!-- end row -->
    </div>
</div>
<!-- /Page Wrapper -->
@endsection
