<?php $page = 'connected-apps'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
		<div class="page-wrapper">
			<div class="content bg-white">
				<div class="d-md-flex d-block align-items-center justify-content-between border-bottom pb-3">
					<div class="my-auto mb-2">
						<h3 class="page-title mb-1">General Settings</h3>
                     	<nav>
                       		<ol class="breadcrumb mb-0">
                          		<li class="breadcrumb-item">
                            		<a href="{{url('index')}}">Dashboard</a>
                          		</li>
                          		<li class="breadcrumb-item">
                            		<a href="javascript:void(0);">Settings</a>
                          		</li>
                          		<li class="breadcrumb-item active" aria-current="page">General Settings</li>
                        	</ol>
                      	</nav>
                    </div>
                    <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
                      	<div class="pe-1 mb-2">
                       		<a href="#" class="btn btn-outline-light bg-white btn-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Refresh" data-bs-original-title="Refresh">
                         		<i class="ti ti-refresh"></i>
                        	</a>
                        </div>
                    </div>
                </div>
				<div class="row">
					<div class="col-xxl-2 col-xl-3">
						<div class="pt-3 d-flex flex-column list-group mb-4">
							<a href="{{url('profile-settings')}}" class="d-block rounded p-2">Profile Settings</a>
							<a href="{{url('security-settings')}}" class="d-block rounded p-2">Security Settings</a>
							<a href="{{url('notifications-settings')}}" class="d-block rounded p-2">Notifications</a>
							<a href="{{url('connected-apps')}}" class="d-block rounded active p-2">Connected Apps</a>
						</div>
					</div>
					<div class="col-xxl-10 col-xl-9">
						<div class="flex-fill border-start ps-3">
							<div class="d-flex align-items-center justify-content-between flex-wrap border-bottom mb-3 pt-3">
								<div class="mb-3">
									<h5>Connected Apps</h5>
									<p>Upload your photo & personal details here</p>
								</div>
							</div>
							<div class="d-block">
								<div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded bg-white">
									<div class="">
										<div class="d-flex align-items-center">
											<span class="flex-shrink-0 mb-3 me-2 border rounded p-2 d-flex align-items-center justify-content-center"><img src="{{URL::asset('build/img/icons/connected-app-01.svg')}}" alt="Img"></span>
											<div class="mb-3">
												<h6>Slack</h6>
												<p>Team communication platform with channels for group discussions and direct messaging.</p>
											</div>
										</div>									
									</div>
									<div class="mb-3">
										<a href="#" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#connect_app">Connect</a>
									</div>
								</div>
								<div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded bg-white">
									<div class="">
										<div class="d-flex align-items-center">
											<span class="flex-shrink-0 mb-3 me-2 border rounded p-2 d-flex align-items-center justify-content-center"><img src="{{URL::asset('build/img/icons/connected-app-02.svg')}}" alt="Img"></span>
											<div class="mb-3">
												<h6>Google Calendar</h6>
												<p>Google Calendar is a web-based scheduling tool that allows users to create, manage, and share events.</p>
											</div>
										</div>									
									</div>
									<div class="mb-3">
										<a href="#" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#connect_app_two">Connect</a>
									</div>
								</div>
								<div class="d-flex align-items-center justify-content-between flex-wrap border mb-3 p-3 pb-0 rounded bg-white">
									<div class="">
										<div class="d-flex align-items-center">
											<span class="flex-shrink-0 mb-3 me-2 border rounded p-2 d-flex align-items-center justify-content-center"><img src="{{URL::asset('build/img/icons/connected-app-03.svg')}}" alt="Img"></span>
											<div class="mb-3">
												<h6>Gmail</h6>
												<p>Gmail is a free email service by Google that offers robust spam protection & 15GB of storage.</p>
											</div>
										</div>									
									</div>
									<div class="mb-3">
										<a href="#" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#connect_app_three">Connect</a>
									</div>
								</div>
								<div class="d-flex align-items-center justify-content-between flex-wrap border mb-0 p-3 pb-0 rounded bg-white">
									<div class="">
										<div class="d-flex align-items-center">
											<span class="flex-shrink-0 mb-3 me-2 border rounded p-2 d-flex align-items-center justify-content-center"><img src="{{URL::asset('build/img/icons/connected-app-04.svg')}}" alt="Img"></span>
											<div class="mb-3">
												<h6>Github</h6>
												<p>GitHub is a web-based platform for version control and collaboration, allowing developers to host and review code</p>
											</div>
										</div>									
									</div>
									<div class="mb-3">
										<a href="#" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#connect_app_four">Connect</a>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- /Page Wrapper -->	
    @component('components.modal-popup')
    @endcomponent
@endsection
