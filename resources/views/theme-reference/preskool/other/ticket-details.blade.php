<?php $page = 'ticket-details'; ?>
@extends('layout.mainlayout')
@section('content')
   <!-- Page Wrapper -->
		<div class="page-wrapper">
			<div class="content pb-lg-4 pb-2">
				<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
					<div class="my-auto mb-2">
						<h3 class="page-title mb-1">Tickets</h3>
						 <nav>
							   <ol class="breadcrumb mb-0">
									<li class="breadcrumb-item">
										<a href="{{url('index')}}">Dashboard</a>
									  </li>
									  <li class="breadcrumb-item">
										Support
									  </li>
									  <li class="breadcrumb-item active" aria-current="page">Tickets</li>
								</ol>
						  </nav>
					</div>
					<div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
						<div class="pe-1 mb-2">
							<a href="#" class="btn btn-outline-light bg-white btn-icon me-1" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Refresh" data-bs-original-title="Refresh">
							   <i class="ti ti-refresh"></i>
							  </a>
						  </div>
						<div class="pe-1 mb-2">
							  <button type="button" class="btn btn-outline-light bg-white btn-icon me-1" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Print" data-bs-original-title="Print">
							   <i class="ti ti-printer"></i>
							  </button>
						   </div>    
						  <div class="dropdown me-2 mb-2">
							<a href="javascript:void(0);"  class="dropdown-toggle btn btn-light fw-medium d-inline-flex align-items-center" data-bs-toggle="dropdown">
							  <i class="ti ti-file-export me-2"></i>Export
							  </a>
							  <ul class="dropdown-menu  dropdown-menu-end p-3">
								 <li>
									 <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-file-type-pdf me-2"></i>Export as PDF</a>
								  </li>
								  <li>
									  <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-file-type-xls me-2"></i>Export as Excel </a>
								  </li>
							  </ul>	
						  </div>                  
						<div class="mb-2">
							<a href="#" data-bs-toggle="modal" data-bs-target="#add_ticket" class="btn btn-primary"><i class="ti ti-square-rounded-plus me-2"></i>Add New Ticket</a>
						</div>
					  </div>
				</div>

				<div class="row">

					<!-- Tickets -->
					<div class="col-xl-8 col-xxl-9">

						<!-- Ticket List -->
						<div class="card">
							<div class="card-header d-flex align-items-center justify-content-between p-3 pb-0">
								<h5 class="text-primary mb-3">IT Support</h5>
								<div class="d-flex align-items-center">
									<span class="badge bg-danger mb-3 me-3"><i class="ti ti-circle-filled fs-5 me-1"></i>High</span>
									<div class="mb-3">
										<select class="select">
											<option>Mark as Public</option>
											<option selected>Mark as Private</option>
										</select>
									</div>
								</div>
							</div>
							<div class="card-body p-0">
								<div class="ticket-information ticket-details">
									<div class="d-flex align-items-center justify-content-between flex-wrap p-3 pb-0 border-bottom">
										<div class="d-flex align-items-center flex-wrap">
											<div class="mb-3">
												<span class="badge bg-pending rounded-pill mb-1">#TK0003</span>
												<div class="d-flex align-items-center mb-2">
													<h5 class="fw-semibold me-2">Laptop Issue</h5>
													<span class="badge bg-outline-danger d-flex align-items-center ms-1">Open</span>
												</div>
												<div class="d-flex align-items-center flex-wrap">
													<p class="text-gray d-flex align-items-center mb-1 me-2"><i class="ti ti-calendar-bolt me-1"></i>Updated 22 hours ago</p>
													<p class="text-gray d-flex align-items-center mb-1"><i class="ti ti-message-share me-1"></i>9 Comments</p>
												</div>
											</div>
										</div>
										<div class="mb-3">
											<a href="#" class="btn btn-primary"><i class="ti ti-reload me-1"></i>Post a Reply</a>
										</div>
									</div>
									<div class="border-bottom p-3">
										<div>
											<p class="mb-3">For the past week, my laptop has been experiencing intermittent freezing issues. 
												The freezes occur randomly, approximately 3-4 times a day, and last about 30-60 
												seconds each time. During these freezes, the cursor becomes unresponsive, and I am 
												unable to click on anything or use keyboard shortcuts. 
												The issue usually resolves itself, but it significantly disrupts my work.
											</p>
											<ul class="list-styled-dotted">
												<li class="ms-4 mb-3">I first noticed the problem on February 1, 2024, while using Google Meet for a video conference. Since then, the issue has occurred during various tasks, including browsing with Chrome, using Microsoft Office applications, and even when the laptop is idle.</li>
												<li class="ms-4">Error messages: No specific error messages have appeared, but the Task Manager (when accessible) shows a spike in CPU usage to 100% during these freezes.</li>
											</ul>
										</div>
										<div class="mt-4">
											<div class="d-flex align-items-center mb-3">
												<span class="avatar avatar-xl me-2 flex-shrink-0"><img src="{{URL::asset('build/img/students/student-11.jpg')}}" alt=""></span>
												<div>
													<h6 class="mb-1">James Hendriques</h6>
													<p><i class="ti ti-calendar-bolt me-1"></i>Updated 5 hours ago</p>
												</div>
											</div>
											<div>
												<div class="mb-3">
													<p>This issue disrupts meetings, delays task completion, and affects my overall productivity.</p>
												</div>
												<span class="badge bg-light">Screenshot.png<i class="ti ti-download ms-1"></i></span>
												<div class="d-flex align-items-center my-3">
													<a href="#" class="d-inline-flex align-items-center text-primary fw-medium me-3"><i class="ti ti-reload me-1"></i>Reply</a>
													<p class="text-gray d-flex align-items-center"><i class="ti ti-message-share me-1"></i>1 Comments</p>
												</div>
											</div>
										</div>
										<div class="bg-light-300 rounded p-3">
											<div class="d-flex align-items-center mb-3">
												<span class="avatar avatar-xl me-2 flex-shrink-0"><img src="{{URL::asset('build/img/profiles/avatar-19.jpg')}}" alt=""></span>
												<div>
													<h6 class="mb-1">Support Agent Angio</h6>
													<p><i class="ti ti-calendar-bolt me-1"></i>Updated 5 hours ago</p>
												</div>
											</div>
											<div>
												<div class="mb-3">
													<p>Switch on the side panel & update the OS,  Login in to the device manager and update the password</p>
												</div>
												<span class="badge bg-light">Screenshot.png<i class="ti ti-download ms-1"></i></span>
												<div class="d-flex align-items-center mt-3">
													<a href="#" class="d-inline-flex align-items-center text-primary fw-medium"><i class="ti ti-reload me-1"></i>Reply</a>												
												</div>
											</div>
										</div>
									</div>
									<div class="border-bottom p-3">
										<div>
											<div class="d-flex align-items-center mb-3">
												<span class="avatar avatar-xl me-2 flex-shrink-0"><img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" alt=""></span>
												<div>
													<h6 class="mb-1">Marilyn Siegle</h6>
													<p><i class="ti ti-calendar-bolt me-1"></i>Updated 6 hours ago</p>
												</div>
											</div>
											<div>
												<div class="mb-3">
													<p>Check the System and Application logs in the Event Viewer for warnings or errors that coincide with the times the freezes occur.</p>
												</div>
												<div class="d-flex align-items-center">
													<span class="badge bg-light me-2">Screenshot.png<i class="ti ti-download ms-1"></i></span>
													<span class="badge bg-light me-2">Screenshot.png<i class="ti ti-download ms-1"></i></span>
													<span class="badge bg-light me-2">Screenshot.png<i class="ti ti-download ms-1"></i></span>
													<span class="badge bg-light">Screenshot.png<i class="ti ti-download ms-1"></i></span>
												</div>					
												<div class="d-flex align-items-center mt-3">
													<a href="#" class="d-inline-flex align-items-center text-primary fw-medium"><i class="ti ti-reload me-1"></i>Reply</a>
												</div>
											</div>
										</div>
									</div>
									<div class="border-bottom p-3">
										<div>
											<div class="d-flex align-items-center mb-3">
												<span class="avatar avatar-xl me-2 flex-shrink-0"><img src="{{URL::asset('build/img/profiles/avatar-22.jpg')}}" alt=""></span>
												<div>
													<h6 class="mb-1">Marilyn Siegle</h6>
													<p><i class="ti ti-calendar-bolt me-1"></i>Updated 6 hours ago</p>
												</div>
											</div>
											<div>
												<div class="mb-3">
													<p>Check for any pending updates and installing them to see if it resolves the issue</p>
												</div>					
												<div class="d-flex align-items-center mt-3">
													<a href="#" class="d-inline-flex align-items-center text-primary fw-medium"><i class="ti ti-reload me-1"></i>Reply</a>
												</div>
											</div>
										</div>
									</div>
								</div>												
							</div>
						</div>
						<!-- /Ticket List -->

						<div class="load-more text-center">
							<a href="#" class="btn btn-primary"><i class="ti ti-loader-3"></i>Load More</a>
						</div>

					</div>
					<!-- /Tickets -->

					<!-- Ticket Details -->
					<div class="col-xl-4 col-xxl-3 theiaStickySidebar">
						<div class="card">
							<div class="card-header p-3">
								<h4>Ticket Details</h4>
							</div>
							<div class="card-body p-0">
								<div class="border-bottom p-3">
									<div class="mb-3">
										<label class="form-label">Change Priority</label>
										<select class="select">
											<option>High</option>
											<option>Low</option>
											<option>Medium</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">Assign To</label>
										<select class="select">
											<option>Jack</option>
											<option>James</option>
											<option>Marilyn</option>
										</select>
									</div>
									<div class="mb-0">
										<label class="form-label">Ticket Status</label>
										<select class="select">
											<option>Open</option>
											<option>Closed</option>
											<option>Medium</option>
										</select>
									</div>
								</div>
								<div class="d-flex align-items-center border-bottom p-3">
									<span class="avatar avatar-md me-2 flex-shrink-0"><img src="{{URL::asset('build/img/teachers/teacher-03.jpg')}}" class="rounded-circle" alt=""></span>
									<div>
										<span class="text-gray fs-12">User</span>
										<p class="text-dark">Hellana</p>
									</div>
								</div>
								<div class="d-flex align-items-center border-bottom p-3">
									<span class="avatar avatar-md me-2 flex-shrink-0"><img src="{{URL::asset('build/img/teachers/teacher-04.jpg')}}" class="rounded-circle" alt=""></span>
									<div>
										<span class="text-gray fs-12">Support Agent</span>
										<p class="text-dark">Angio</p>
									</div>
								</div>
								<div class="border-bottom p-3">
									<span class="text-gray fs-12">Category</span>
									<p class="text-dark">Repair & Service</p>
								</div>
								<div class="border-bottom p-3">
									<span class="text-gray fs-12">Email</span>
									<p class="text-dark">Hellana@gmail.com</p>
								</div>
								<div class="p-3">
									<span class="text-gray fs-12">Last Updated / Closed On</span>
									<p class="text-dark">25 May 2024</p>
								</div>
							</div>
						</div>
					</div>
					<!-- /Ticket Details -->

				</div>

			</div>
		</div>
		<!-- /Page Wrapper -->

    @component('components.modal-popup')
    @endcomponent
@endsection
