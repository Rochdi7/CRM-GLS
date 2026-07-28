<?php $page = 'ui-progress'; ?>
@extends('layout.mainlayout')
@section('content')
   <!-- Page Wrapper -->
   <div class="page-wrapper">
    <div class="content">
        @component('components.breadcrumb')
        @slot('title')
     Progress Bar
        @endslot
    @endcomponent	
        <!-- start row -->
        <div class="row">

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Basic Progress</h5>
                    </div>
                    <div class="card-body">
                        <p>Use the <code>.progress</code> as a wrapper to indicate the max value of the progress bar.</p>

                        <div class="progress mb-3" role="progressbar" aria-label="Basic example" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: 0%"></div>
                        </div>
                        <div class="progress mb-3" role="progressbar" aria-label="Basic example" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: 25%"></div>
                        </div>
                        <div class="progress mb-3" role="progressbar" aria-label="Basic example" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: 50%"></div>
                        </div>
                        <div class="progress mb-3" role="progressbar" aria-label="Basic example" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: 75%"></div>
                        </div>
                        <div class="progress" role="progressbar" aria-label="Basic example" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: 100%"></div>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Backgrounds</h5>
                    </div>
                    <div class="card-body">
                        <p>Use background utility classes to change the appearance of individual progress bars.</p>

                        <div class="progress mb-3" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-secondary" style="width: 20%"></div>
                        </div>
                        <div class="progress mb-3" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-warning" style="width: 40%"></div>
                        </div>
                        <div class="progress mb-3" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-info" style="width: 60%"></div>
                        </div>
                        <div class="progress mb-3" role="progressbar" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-success" style="width: 80%"></div>
                        </div>
                        <div class="progress" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-danger" style="width: 100%"></div>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Striped Progress</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Add <code>.progress-bar-striped</code> to any <code>.progress-bar</code> to apply a stripe via CSS gradient over the progress bar’s background color.</p>

                        <div class="progress mb-3" role="progressbar" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar progress-bar-striped" style="width: 10%">
                            </div>
                        </div>
                        <div class="progress mb-3" role="progressbar" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar progress-bar-striped bg-secondary" style="width: 25%">
                            </div>
                        </div>
                        <div class="progress mb-3" role="progressbar" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar progress-bar-striped bg-success" style="width: 50%">
                            </div>
                        </div>
                        <div class="progress mb-3" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar progress-bar-striped bg-info" style="width: 75%">
                            </div>
                        </div>
                        <div class="progress" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar progress-bar-striped bg-warning" style="width: 100%"></div>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Progress Height</h5>
                    </div>

                    <div class="card-body">
                        <p class="text-muted">We only set a <code>height</code> value on the <code>.progress</code>, so if you change that value the inner <code>.progress-bar</code> will automatically resize accordingly. Use <code>.progress-sm</code>,<code>.progress-md</code>,<code>.progress-lg</code>,<code>.progress-xl</code> classes.</p>

                        <div class="progress mb-3" style="height: 1px;">
                            <div class="progress-bar bg-danger" role="progressbar" style="width: 25%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="progress mb-3" style="height: 3px;">
                            <div class="progress-bar" role="progressbar" style="width: 25%; height: 20px;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="progress mb-3 progress-sm">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="progress mb-3 progress-md">
                            <div class="progress-bar bg-info" role="progressbar" style="width: 50%" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="progress progress-lg mb-3">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="progress progress-xl">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 85%" aria-valuenow="85" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Progress with Labels</h5>
                    </div>
                    <div class="card-body">
                        <p>Add labels to your progress bars by placing text within the <code>.progress-bar</code>.</p>

                        <div class="progress mb-3" role="progressbar" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: 10%;">10%</div>
                        </div>
                        <div class="progress mb-3" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-secondary" style="width: 20%;">20%</div>
                        </div>
                        <div class="progress mb-3" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-success" style="width: 40%;">40%</div>
                        </div>
                        <div class="progress mb-3" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-info" style="width: 60%;">60%</div>
                        </div>
                        <div class="progress mb-0" role="progressbar" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-warning" style="width: 80%;">80%</div>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Multiple Bars with Sizes</h5>
                    </div>
                    <div class="card-body">
                        <p>You can include multiple progress components inside a container with <code>.progress-stacked</code> to create a single stacked progress bar.</p>

                        <div class="progress-stacked progress-xs mb-3">
                            <div class="progress-bar" role="progressbar" style="width: 5%" aria-valuenow="5" aria-valuemin="0" aria-valuemax="100"></div>
                            <div class="progress-bar bg-secondary" role="progressbar" style="width: 10%" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100"></div>
                            <div class="progress-bar bg-success" role="progressbar" style="width: 15%" aria-valuenow="15" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="progress-stacked progress-sm mb-3">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: 10%" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100"></div>
                            <div class="progress-bar bg-info" role="progressbar" style="width: 15%" aria-valuenow="15" aria-valuemin="0" aria-valuemax="100"></div>
                            <div class="progress-bar bg-danger" role="progressbar" style="width: 20%" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="progress-stacked mb-3">
                            <div class="progress-bar bg-info" role="progressbar" style="width: 15%" aria-valuenow="15" aria-valuemin="0" aria-valuemax="100"></div>
                            <div class="progress-bar bg-success" role="progressbar" style="width: 20%" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100"></div>
                            <div class="progress-bar" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="progress-stacked progress-lg mb-3">
                            <div class="progress-bar bg-secondary" role="progressbar" style="width: 20%" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100"></div>
                            <div class="progress-bar bg-danger" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                            <div class="progress-bar bg-info" role="progressbar" style="width: 30%" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="progress-stacked progress-xl mb-0">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                            <div class="progress-bar bg-danger" role="progressbar" style="width: 30%" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                            <div class="progress-bar bg-warning" role="progressbar" style="width: 35%" aria-valuenow="35" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Animated Stripped Progress</h5>
                    </div>
                    <div class="card-body">
                        <p>The striped gradient can also be animated. Add <code>.progress-bar-animated</code> to <code>.progress-bar</code> to animate the stripes right to left via CSS3 animations.</p>

                        <div class="progress mb-3" role="progressbar" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 10%"></div>
                        </div>
                        <div class="progress mb-3" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-secondary progress-bar-striped progress-bar-animated" style="width: 20%"></div>
                        </div>
                        <div class="progress mb-3" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" style="width: 40%"></div>
                        </div>
                        <div class="progress mb-3" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-info progress-bar-striped progress-bar-animated" style="width: 60%"></div>
                        </div>
                        <div class="progress" role="progressbar" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-warning progress-bar-striped progress-bar-animated" style="width: 80%"></div>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Gradient Progress</h5>
                    </div>
                    <div class="card-body">
                        <p>Add <code>.bg-primary-gradient</code> to any <code>.progress-bar</code> to apply a CSS gradient over the progress bar’s background color.</p>

                        <div class="progress mb-3 progress-animate" role="progressbar" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-primary-gradient" style="width: 10%"></div>
                        </div>
                        <div class="progress mb-3 progress-animate" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-secondary-gradient" style="width: 20%"></div>
                        </div>
                        <div class="progress mb-3 progress-animate" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-success-gradient" style="width: 40%"></div>
                        </div>
                        <div class="progress mb-3 progress-animate" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-info-gradient" style="width: 60%"></div>
                        </div>
                        <div class="progress progress-animate" role="progressbar" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-warning-gradient" style="width: 80%"></div>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->
            
        </div>
        <!-- end row -->
    </div>
</div>
<!-- /Page Wrapper -->
@endsection
