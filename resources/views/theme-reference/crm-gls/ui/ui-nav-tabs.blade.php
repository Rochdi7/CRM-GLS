<?php $page = 'ui-nav-tabs'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="page-wrapper">
    <div class="content">
        @component('components.breadcrumb')
        @slot('title')
        Nav & Tabs
        @endslot
    @endcomponent	
       
        <!-- start row -->
        <div class="row">

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Default Tabs</h5>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs default-tabs mb-3">
                            <li class="nav-item">
                                <a href="#home" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                    Home
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#profile" data-bs-toggle="tab" aria-expanded="true" class="nav-link active">
                                    Profile
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#message" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                    Settings
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" data-bs-toggle="tab" aria-expanded="false" class="nav-link disabled">
                                    Disabled
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane" id="home">
                                <p class="mb-0">Welcome to the dashboard! Access key metrics, recent updates, and quick links to manage your activity. Use this panel to stay on top of your workflow and performance goals.</p>
                            </div>
                            <div class="tab-pane show active" id="profile">
                                <p class="mb-0">Manage your profile details, update your contact info, and review your login activity. Keeping your account information accurate ensures a smooth and secure experience.</p>
                            </div>
                            <div class="tab-pane" id="message">
                                <p class="mb-0">View recent messages, send new replies, and stay connected with your team. The messaging center helps you manage conversations efficiently and never miss important updates.</p>
                            </div>
                        </div>

                    </div> <!-- end card-body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Tabs Justified</h5>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-pills bg-nav-pills nav-justified mb-3">
                            <li class="nav-item">
                                <a href="#home2" data-bs-toggle="tab" aria-expanded="false" class="nav-link rounded-0">
                                    Home
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#profile2" data-bs-toggle="tab" aria-expanded="true" class="nav-link rounded-0 active">
                                    Profile
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#messages2" data-bs-toggle="tab" aria-expanded="false" class="nav-link rounded-0">
                                    Settings
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane" id="home2">
                                <p class="mb-0">Welcome to the dashboard! Access key metrics, recent updates, and quick links to manage your activity. Use this panel to stay on top of your workflow and performance goals.</p>
                            </div>
                            <div class="tab-pane show active" id="profile2">
                                <p class="mb-0">Manage your profile details, update your contact info, and review your login activity. Keeping your account information accurate ensures a smooth and secure experience.</p>
                            </div>
                            <div class="tab-pane" id="messages2">
                                <p class="mb-0">View recent messages, send new replies, and stay connected with your team. The messaging center helps you manage conversations efficiently and never miss important updates. </p>
                            </div>
                        </div>

                    </div> <!-- end card-body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div>
        <!-- end row -->

        <!-- start row -->
        <div class="row">

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Solid Tabs</h5>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs nav-solid-primary mb-3">
                            <li class="nav-item"><a class="nav-link active" href="#solid-tab1" data-bs-toggle="tab">Home</a></li>
                            <li class="nav-item"><a class="nav-link" href="#solid-tab2" data-bs-toggle="tab">Profile</a></li>
                            <li class="nav-item"><a class="nav-link" href="#solid-tab3" data-bs-toggle="tab">Messages</a></li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane show active" id="solid-tab1">
                                <p class="mb-0">Welcome to the dashboard! Access key metrics, recent updates, and quick links to manage your activity. Use this panel to stay on top of your workflow and performance goals.</p>
                            </div>
                            <div class="tab-pane" id="solid-tab2">
                                <p class="mb-0">Manage your profile details, update your contact info, and review your login activity. Keeping your account information accurate ensures a smooth and secure experience.</p>
                            </div>
                            <div class="tab-pane" id="solid-tab3">
                                <p class="mb-0">View recent messages, send new replies, and stay connected with your team. The messaging center helps you manage conversations efficiently and never miss important updates.</p>
                            </div>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Solid Justified</h5>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs nav-solid-primary nav-justified mb-3">
                            <li class="nav-item"><a class="nav-link active" href="#solid-justified-tab1" data-bs-toggle="tab">Home</a></li>
                            <li class="nav-item"><a class="nav-link" href="#solid-justified-tab2" data-bs-toggle="tab">Profile</a></li>
                            <li class="nav-item"><a class="nav-link" href="#solid-justified-tab3" data-bs-toggle="tab">Messages</a></li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane show active" id="solid-justified-tab1">
                                <p class="mb-0">Welcome to the dashboard! Access key metrics, recent updates, and quick links to manage your activity. Use this panel to stay on top of your workflow and performance goals.</p>
                            </div>
                            <div class="tab-pane" id="solid-justified-tab2">
                                <p class="mb-0">Manage your profile details, update your contact info, and review your login activity. Keeping your account information accurate ensures a smooth and secure experience.</p>
                            </div>
                            <div class="tab-pane" id="solid-justified-tab3">
                                <p class="mb-0">View recent messages, send new replies, and stay connected with your team. The messaging center helps you manage conversations efficiently and never miss important updates.</p>
                            </div>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div>
        <!-- end row -->

        <!-- start row -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Solid Rounded</h5>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs nav-solid-success nav-tabs-rounded mb-3">
                            <li class="nav-item"><a class="nav-link active" href="#solid-rounded-tab1" data-bs-toggle="tab">Home</a></li>
                            <li class="nav-item"><a class="nav-link" href="#solid-rounded-tab2" data-bs-toggle="tab">Profile</a></li>
                            <li class="nav-item"><a class="nav-link" href="#solid-rounded-tab3" data-bs-toggle="tab">Messages</a></li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane show active" id="solid-rounded-tab1">
                                <p class="mb-0">Welcome to the dashboard! Access key metrics, recent updates, and quick links to manage your activity. Use this panel to stay on top of your workflow and performance goals.</p>
                            </div>
                            <div class="tab-pane" id="solid-rounded-tab2">
                                <p class="mb-0">Manage your profile details, update your contact info, and review your login activity. Keeping your account information accurate ensures a smooth and secure experience.</p>
                            </div>
                            <div class="tab-pane" id="solid-rounded-tab3">
                                <p class="mb-0">View recent messages, send new replies, and stay connected with your team. The messaging center helps you manage conversations efficiently and never miss important updates.</p>
                            </div>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Rounded Justified</h5>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs nav-solid-success nav-tabs-rounded nav-justified mb-3">
                            <li class="nav-item"><a class="nav-link active" href="#solid-rounded-justified-tab1" data-bs-toggle="tab">Home</a></li>
                            <li class="nav-item"><a class="nav-link" href="#solid-rounded-justified-tab2" data-bs-toggle="tab">Profile</a></li>
                            <li class="nav-item"><a class="nav-link" href="#solid-rounded-justified-tab3" data-bs-toggle="tab">Messages</a></li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane show active" id="solid-rounded-justified-tab1">
                                <p class="mb-0">Welcome to the dashboard! Access key metrics, recent updates, and quick links to manage your activity. Use this panel to stay on top of your workflow and performance goals.</p>
                            </div>
                            <div class="tab-pane" id="solid-rounded-justified-tab2">
                                <p class="mb-0">Manage your profile details, update your contact info, and review your login activity. Keeping your account information accurate ensures a smooth and secure experience.</p>
                            </div>
                            <div class="tab-pane" id="solid-rounded-justified-tab3">
                                <p class="mb-0">View recent messages, send new replies, and stay connected with your team. The messaging center helps you manage conversations efficiently and never miss important updates.</p>
                            </div>
                        </div>
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
                        <h5 class="card-title">Tabs Bordered</h5>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs nav-bordered mb-3">
                            <li class="nav-item">
                                <a href="#home-b1" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                    <span class="d-md-inline-block">Home</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#profile-b1" data-bs-toggle="tab" aria-expanded="true" class="nav-link active">
                                    <span class="d-md-inline-block">Profile</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#messages-b1" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                    <span class="d-md-inline-block">Messages</span>
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane" id="home-b1">
                                <p class="mb-0">Welcome to the dashboard! Access key metrics, recent updates, and quick links to manage your activity. Use this panel to stay on top of your workflow and performance goals.</p>
                            </div>
                            <div class="tab-pane show active" id="profile-b1">
                                <p class="mb-0">Manage your profile details, update your contact info, and review your login activity. Keeping your account information accurate ensures a smooth and secure experience.</p>
                            </div>
                            <div class="tab-pane" id="messages-b1">
                                <p class="mb-0">View recent messages, send new replies, and stay connected with your team. The messaging center helps you manage conversations efficiently and never miss important updates.</p>
                            </div>
                        </div>

                    </div> <!-- end card-body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Tabs Bordered Justified</h5>
                    </div>

                    <div class="card-body">
                        <ul class="nav nav-tabs nav-justified nav-bordered nav-bordered-success mb-3">
                            <li class="nav-item">
                                <a href="#home-b2" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                    <span class="d-md-inline-block">Home</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#profile-b2" data-bs-toggle="tab" aria-expanded="true" class="nav-link active">
                                    <span class="d-md-inline-block">Profile</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#messages-b2" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                    <span class="d-md-inline-block">Messages</span>
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane" id="home-b2">
                                <p class="mb-0">Welcome to the dashboard! Access key metrics, recent updates, and quick links to manage your activity. Use this panel to stay on top of your workflow and performance goals.</p>
                            </div>
                            <div class="tab-pane show active" id="profile-b2">
                                <p class="mb-0">Manage your profile details, update your contact info, and review your login activity. Keeping your account information accurate ensures a smooth and secure experience.</p>
                            </div>
                            <div class="tab-pane" id="messages-b2">
                                <p class="mb-0">View recent messages, send new replies, and stay connected with your team. The messaging center helps you manage conversations efficiently and never miss important updates.</p>
                            </div>
                        </div>

                    </div> <!-- end card-body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div>
        <!-- end row -->

        <!-- start row -->
        <div class="row">

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Tabs Vertical Left</h5>
                    </div>
                    <div class="card-body">

                        <!-- start row -->
                        <div class="row">
                            <div class="col-sm-3 mb-2 mb-sm-0">
                                <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                                    <a class="nav-link active show" id="v-pills-home-tab" data-bs-toggle="pill" href="#v-pills-home" role="tab" aria-controls="v-pills-home" aria-selected="true">
                                        Home
                                    </a>
                                    <a class="nav-link" id="v-pills-profile-tab" data-bs-toggle="pill" href="#v-pills-profile" role="tab" aria-controls="v-pills-profile" aria-selected="false">
                                        Profile
                                    </a>
                                    <a class="nav-link" id="v-pills-messages-tab" data-bs-toggle="pill" href="#v-pills-messages" role="tab" aria-controls="v-pills-messages" aria-selected="false">
                                        Messages
                                    </a>
                                </div>
                            </div> <!-- end col -->

                            <div class="col-sm-9">
                                <div class="tab-content" id="v-pills-tabContent">
                                    <div class="tab-pane fade active show" id="v-pills-home" role="tabpanel" aria-labelledby="v-pills-home-tab">
                                        <p class="mb-0">Welcome to the dashboard! Access key metrics, recent updates, and quick links to manage your activity. Use this panel to stay on top of your workflow and performance goals.</p>
                                    </div>
                                    <div class="tab-pane fade" id="v-pills-profile" role="tabpanel" aria-labelledby="v-pills-profile-tab">
                                        <p class="mb-0">Manage your profile details, update your contact info, and review your login activity. Keeping your account information accurate ensures a smooth and secure experience.</p>
                                    </div>
                                    <div class="tab-pane fade" id="v-pills-messages" role="tabpanel" aria-labelledby="v-pills-messages-tab">
                                        <p class="mb-0">View recent messages, send new replies, and stay connected with your team. The messaging center helps you manage conversations efficiently and never miss important updates.</p>
                                    </div>
                                </div> <!-- end tab-content-->
                            </div> <!-- end col -->

                        </div>
                        <!-- end row -->

                    </div> <!-- end card-body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Tabs Vertical Right</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-9">
                                <div class="tab-content" id="v-pills-tabContent-right">
                                    <div class="tab-pane fade active show" id="v-pills-home2" role="tabpanel" aria-labelledby="v-pills-home-tab">
                                        <p class="mb-0">Welcome to the dashboard! Access key metrics, recent updates, and quick links to manage your activity. Use this panel to stay on top of your workflow and performance goals.</p>
                                    </div>
                                    <div class="tab-pane fade" id="v-pills-profile2" role="tabpanel" aria-labelledby="v-pills-profile-tab">
                                        <p class="mb-0">Manage your profile details, update your contact info, and review your login activity. Keeping your account information accurate ensures a smooth and secure experience.</p>
                                    </div>
                                    <div class="tab-pane fade" id="v-pills-messages2" role="tabpanel" aria-labelledby="v-pills-messages-tab">
                                        <p class="mb-0">View recent messages, send new replies, and stay connected with your team. The messaging center helps you manage conversations efficiently and never miss important updates.</p>
                                    </div>
                                </div> <!-- end tabcontent-->
                            </div> <!-- end col -->

                            <div class="col-sm-3 mt-2 mt-sm-0">
                                <div class="nav flex-column nav-pills nav-pills-secondary" id="v-pills-tab2" role="tablist" aria-orientation="vertical">
                                    <a class="nav-link active show" id="v-pills-home-tab2" data-bs-toggle="pill" href="#v-pills-home2" role="tab" aria-controls="v-pills-home2" aria-selected="true">
                                        <span class="d-md-inline-block">Home</span>
                                    </a>
                                    <a class="nav-link" id="v-pills-profile-tab2" data-bs-toggle="pill" href="#v-pills-profile2" role="tab" aria-controls="v-pills-profile2" aria-selected="false">
                                        <span class="d-md-inline-block">Profile</span>
                                    </a>
                                    <a class="nav-link" id="v-pills-messages-tab2" data-bs-toggle="pill" href="#v-pills-messages2" role="tab" aria-controls="v-pills-messages2" aria-selected="false">
                                        <span class="d-md-inline-block">Messages</span>
                                    </a>
                                </div>
                            </div> <!-- end col -->
                            
                        </div> 
                        <!-- end row -->

                    </div> <!-- end card-body -->
                </div> <!-- end card -->
            </div> <!-- end col -->
            
        </div>
        <!-- end row -->

    </div>
</div>
@endsection
