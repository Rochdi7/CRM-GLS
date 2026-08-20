<?php $page = 'chart-js'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="page-wrapper cardhead">
    <div class="content">

        <!-- Page Header -->
        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <h3 class="page-title">Chartjs</h3>
                </div>
            </div>
        </div>
        <!-- /Page Header -->

        <div class="row">

            <!-- Chart -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Bar Chart</div>
                    </div>
                    <div class="card-body">
                        <div>
                            <canvas id="chartBar1" class="h-300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Chart -->

            <!-- Chart -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Transparency </div>
                    </div>
                    <div class="card-body">
                        <div>
                            <canvas id="chartBar2" class="h-300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Chart -->

            <!-- Chart -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Gradient Bar Chart</div>
                    </div>
                    <div class="card-body">
                        <div>
                            <canvas id="chartBar3" class="h-300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Chart -->


            <!-- Chart -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Horizontal Bar Chart</div>
                    </div>
                    <div class="card-body">
                        <div class="chartjs-wrapper-demo">
                            <canvas id="chartBar4" class="h-300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Chart -->

            <!-- Chart -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Horizontal Bar Chart Style2</div>
                    </div>
                    <div class="card-body">
                        <div class="chartjs-wrapper-demo">
                            <canvas id="chartBar5" class="h-300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Chart -->

            <!-- Chart -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Vertical Stacked Bar Chart</div>
                    </div>
                    <div class="card-body">
                        <div class="chartjs-wrapper-demo">
                            <canvas id="chartStacked1" class="h-300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Chart -->

            <!-- Chart -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Horizontal Stacked Bar Chart</div>
                    </div>
                    <div class="card-body">
                        <div class="chartjs-wrapper-demo">
                            <canvas id="chartStacked2" class="h-300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Chart -->

            <!-- Chart -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Line Chart</div>
                    </div>
                    <div class="card-body">
                        <div class="chartjs-wrapper-demo">
                            <canvas id="chartLine1" class="h-300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Chart -->

            <!-- Chart -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Donut Chart</div>
                    </div>
                    <div class="card-body">
                        <div class="chartjs-wrapper-demo">
                            <canvas id="chartPie" class="h-300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Chart -->

            <!-- Chart -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Pie Chart</div>
                    </div>
                    <div class="card-body">
                        <div class="chartjs-wrapper-demo">
                            <canvas id="chartDonut" class="h-300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Chart -->

            <!-- Chart -->
            <div class="col-md-6 d-flex">
                <div class="card w-100">
                    <div class="card-header">
                        <div class="card-title">Area Chart</div>
                    </div>
                    <div class="card-body">
                        <div class="chartjs-wrapper-demo">
                            <canvas id="chartArea1" class="h-300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Chart -->

            <!-- Chart -->
            <div class="col-md-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-header">
                        <div class="card-title">Scatter Chart</div>
                    </div>
                    <div class="card-body">
                        <div class="chartjs-wrapper-demo">
                            <canvas id="chartRadar" class="h-300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Chart -->

        </div>

    </div>
</div> 
@endsection
