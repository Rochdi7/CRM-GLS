<?php $page = 'religion'; ?>
@extends('layout.mainlayout')
@section('content')
   	<!-- Page Wrapper -->
		<div class="page-wrapper">
			<div class="content bg-white">
				<div class="d-md-flex d-block align-items-center justify-content-between border-bottom pb-3">
					<div class="my-auto mb-2">
						<h3 class="page-title mb-1">Academic Settings</h3>
						<nav>
							<ol class="breadcrumb mb-0">
								<li class="breadcrumb-item">
									<a href="{{url('index')}}">Dashboard</a>
								</li>
								<li class="breadcrumb-item">
									<a href="javascript:void(0);">Settings</a>
								</li>
								<li class="breadcrumb-item active" aria-current="page">Academic Settings</li>
							</ol>
						</nav>
					</div>
					<div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
						<div class="pe-1 mb-2">
							<a href="#" class="btn btn-outline-light bg-white btn-icon" data-bs-toggle="tooltip"
								data-bs-placement="top" aria-label="Refresh" data-bs-original-title="Refresh">
								<i class="ti ti-refresh"></i>
							</a>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-xxl-2 col-xl-3">
						<div class="pt-3 d-flex flex-column list-group mb-4">
							<a href="{{url('school-settings')}}" class="d-block rounded p-2">School Settings</a>
							<a href="{{url('religion')}}" class="d-block rounded active p-2">Religion</a>
						</div>
					</div>
					<div class="col-xxl-10 col-xl-9">
						<div class="border-start ps-3">
							<form action="{{url('religion')}}">
								<div
									class="d-flex align-items-center justify-content-between flex-wrap border-bottom pt-3 mb-3">
									<div class="mb-3">
										<h5 class="mb-1">Religion</h5>
										<p>Religion Settings Configuration</p>
									</div>
									<div class="mb-3">
										<a href="#" class="btn btn-outline-light bg-white btn-icon me-2"
											data-bs-toggle="modal" data-bs-target="#add_religion"><i
												class="ti ti-plus"></i></a>
										<button class="btn btn-light me-2" type="button">Cancel</button>
										<button class="btn btn-primary" type="submit">Save</button>
									</div>
								</div>
								<div class="d-md-flex">
									<div class="row flex-fill">
										<div class="col-xxl-4 col-md-6">
											<div
												class="d-flex align-items-center justify-content-between bg-white p-3 border rounded mb-3">
												<h5 class="fs-15 fw-normal">Hindu</h5>
												<div class="d-flex align-items-center">
													<div class="status-toggle modal-status">
														<input type="checkbox" id="user1" class="check">
														<label for="user1" class="checktoggle"> </label>
													</div>
													<div class="d-flex align-items-center ms-3">
														<a href="#" data-bs-toggle="modal"
															data-bs-target="#edit_religion"><i
																class="ti ti-edit me-2"></i></a>
														<a href="#" data-bs-toggle="modal"
															data-bs-target="#delete-modal"><i
																class="ti ti-trash"></i></a>
													</div>
												</div>
											</div>
										</div>
										<div class="col-xxl-4 col-md-6">
											<div
												class="d-flex align-items-center justify-content-between bg-white p-3 border rounded mb-3">
												<h5 class="fs-15 fw-normal">Christian</h5>
												<div class="d-flex align-items-center">
													<div class="status-toggle modal-status">
														<input type="checkbox" id="user2" class="check">
														<label for="user2" class="checktoggle"> </label>
													</div>
													<div class="d-flex align-items-center ms-3">
														<a href="#" data-bs-toggle="modal"
															data-bs-target="#edit_religion"><i
																class="ti ti-edit me-2"></i></a>
														<a href="#" data-bs-toggle="modal"
															data-bs-target="#delete-modal"><i
																class="ti ti-trash"></i></a>
													</div>
												</div>
											</div>
										</div>
										<div class="col-xxl-4 col-md-6">
											<div
												class="d-flex align-items-center justify-content-between bg-white p-3 border rounded mb-3">
												<h5 class="fs-15 fw-normal">Islam</h5>
												<div class="d-flex align-items-center">
													<div class="status-toggle modal-status">
														<input type="checkbox" id="user3" class="check">
														<label for="user3" class="checktoggle"> </label>
													</div>
													<div class="d-flex align-items-center ms-3">
														<a href="#" data-bs-toggle="modal"
															data-bs-target="#edit_religion"><i
																class="ti ti-edit me-2"></i></a>
														<a href="#" data-bs-toggle="modal"
															data-bs-target="#delete-modal"><i
																class="ti ti-trash"></i></a>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- /Page Wrapper -->

    @component('components.modal-popup')
    @endcomponent
@endsection
