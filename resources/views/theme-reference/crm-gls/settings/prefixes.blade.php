<?php $page = 'prefixes'; ?>
@extends('layout.mainlayout')
@section('content')
  	<!-- Page Wrapper -->
	  <div class="page-wrapper">
		<div class="content bg-white">
			<div class="d-md-flex d-block align-items-center justify-content-between border-bottom pb-3">
				<div class="my-auto mb-2">
					<h3 class="page-title mb-1">Website Settings</h3>
					 <nav>
						   <ol class="breadcrumb mb-0">
							  <li class="breadcrumb-item">
								<a href="{{url('index')}}">Dashboard</a>
							  </li>
							  <li class="breadcrumb-item">
								<a href="javascript:void(0);">Settings</a>
							  </li>
							  <li class="breadcrumb-item active" aria-current="page">Website Settings</li>
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
						<a href="{{url('company-settings')}}" class="d-block rounded p-2">Company Settings</a>
						<a href="{{url('localization')}}" class="d-block rounded p-2">Localization</a>
						<a href="{{url('prefixes')}}" class="d-block rounded p-2 active">Prefixes</a>
						<a href="{{url('preferences')}}" class="d-block rounded p-2">Preferences</a>
						<a href="{{url('social-authentication')}}" class="d-block rounded p-2">Social Authentication</a>
						<a href="{{url('language')}}" class="d-block rounded p-2">Language</a>
					</div>
				</div>
				<div class="col-xxl-10 col-xl-9">
					<div class="border-start ps-3">
						<form action="{{url('prefixes')}}">
							<div class="d-flex align-items-center justify-content-between flex-wrap border-bottom pt-3 mb-3">
								<div class="mb-3">
									<h5 class="mb-1">Prefixes</h5>
									<p>Personalize your path and settings of your website</p>
								</div>
								<div class="mb-3">
									<button class="btn btn-light me-2" type="button">Cancel</button>
									<button class="btn btn-primary" type="submit">Save</button>
								</div>
							</div>
							<div class="d-md-flex d-block">
								<div class="flex-fill">
									<div class="card">
										<div class="card-body p-3 pb-0">
											<div class="row">
												<div class="col-xxl-3 col-xl-4 col-sm-6">
													<div class="mb-3">
														<label class="form-label">Students</label>
														<input type="text" class="form-control">
													</div>
												</div>
												<div class="col-xxl-3 col-xl-4 col-sm-6">
													<div class="mb-3">
														<label class="form-label">Teachers</label>
														<input type="text" class="form-control">
													</div>
												</div>
												<div class="col-xxl-3 col-xl-4 col-sm-6">
													<div class="mb-3">
														<label class="form-label">Parents</label>
														<input type="text" class="form-control">
													</div>
												</div>
												<div class="col-xxl-3 col-xl-4 col-sm-6">
													<div class="mb-3">
														<label class="form-label">Guardians</label>
														<input type="text" class="form-control">
													</div>
												</div>
												<div class="col-xxl-3 col-xl-4 col-sm-6">
													<div class="mb-3">
														<label class="form-label">Subjects</label>
														<input type="text" class="form-control">
													</div>
												</div>
												<div class="col-xxl-3 col-xl-4 col-sm-6">
													<div class="mb-3">
														<label class="form-label">Class</label>
														<input type="text" class="form-control">
													</div>
												</div>
												<div class="col-xxl-3 col-xl-4 col-sm-6">
													<div class="mb-3">
														<label class="form-label">Sections</label>
														<input type="text" class="form-control">
													</div>
												</div>
												<div class="col-xxl-3 col-xl-4 col-sm-6">
													<div class="mb-3">
														<label class="form-label">Home Work</label>
														<input type="text" class="form-control">
													</div>
												</div>
												<div class="col-xxl-3 col-xl-4 col-sm-6">
													<div class="mb-3">
														<label class="form-label">Department</label>
														<input type="text" class="form-control">
													</div>
												</div>
												<div class="col-xxl-3 col-xl-4 col-sm-6">
													<div class="mb-3">
														<label class="form-label">Designation</label>
														<input type="text" class="form-control">
													</div>
												</div>
												<div class="col-xxl-3 col-xl-4 col-sm-6">
													<div class="mb-3">
														<label class="form-label">Notice Board</label>
														<input type="text" class="form-control">
													</div>
												</div>
												<div class="col-xxl-3 col-xl-4 col-sm-6">
													<div class="mb-3">
														<label class="form-label">Attendance</label>
														<input type="text" class="form-control">
													</div>
												</div>
												<div class="col-xxl-3 col-xl-4 col-sm-6">
													<div class="mb-3">
														<label class="form-label">Time Table</label>
														<input type="text" class="form-control">
													</div>
												</div>
												<div class="col-xxl-3 col-xl-4 col-sm-6">
													<div class="mb-3">
														<label class="form-label">Sports</label>
														<input type="text" class="form-control">
													</div>
												</div>
												<div class="col-xxl-3 col-xl-4 col-sm-6">
													<div class="mb-3">
														<label class="form-label">Syllabus</label>
														<input type="text" class="form-control">
													</div>
												</div>
												<div class="col-xxl-3 col-xl-4 col-sm-6">
													<div class="mb-3">
														<label class="form-label">Class Room</label>
														<input type="text" class="form-control">
													</div>
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
@endsection
