<?php $page = 'tickets'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
		<div class="page-wrapper">
			<div class="content pb-lg-4 pb-2">
				
				<div class="row">
					<!-- Page Header -->
					<div class="col-md-12">
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
					</div>
					<!-- /Page Header -->
				</div>

				<div class="d-flex align-items-center justify-content-between flex-wrap p-3 bg-white pb-0 mb-4">
					<div class="dropdown me-2 mb-3">
						<a href="javascript:void(0);" class="dropdown-toggle text-default fw-medium d-inline-flex align-items-center p-1 border-0 fs-18 fw-semibold" data-bs-toggle="dropdown">
							All Tickets
						</a>
						  <ul class="dropdown-menu p-3">
							 <li>
								 <a href="javascript:void(0);" class="dropdown-item rounded-1">Open</a>
							  </li>
							  <li>
								  <a href="javascript:void(0);" class="dropdown-item rounded-1">Inprogress</a>
							  </li>
							  <li>
								  <a href="javascript:void(0);" class="dropdown-item rounded-1">Closed</a>
							  </li>
							  <li>
								  <a href="javascript:void(0);" class="dropdown-item rounded-1">Reopened</a>
							  </li>
						  </ul>	
					</div> 
					<div class="d-flex align-items-center flex-wrap">
						<div class="input-icon-start mb-3 me-2 position-relative">
                            <span class="icon-addon">
                                <i class="ti ti-calendar"></i>
                            </span>
							<input type="text" class="form-control  date-range bookingrange" placeholder="Select" value="Academic Year : 2024 / 2025">
						</div>
						<div class="dropdown mb-3 me-2">
							<a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle"
								data-bs-toggle="dropdown" data-bs-auto-close="outside"><i
								class="ti ti-filter me-2"></i>Filter
							</a>
							<div class="dropdown-menu drop-width">
								<form action="{{url('tickets')}}">
									<div class="d-flex align-items-center border-bottom p-3">
										<h4>Filter</h4>
									</div>
									<div class="p-3 pb-0 border-bottom">
										<div class="row">
											<div class="col-md-12">
												<div class="mb-3">
													<label class="form-label">Assigned to</label>
													<select class="select">
														<option>Select</option>
														<option>Janet</option>
														<option>Joann</option>
														<option>Gifford</option>
													</select>
												</div>
											</div>
											<div class="col-md-12">
												<div class="mb-3">
													<label class="form-label">Select Date</label>
													<select class="select">
														<option>Select</option>
														<option>22 May 2024</option>
														<option>23 May 2024</option>
														<option>24 May 2024</option>
													</select>
												</div>
											</div>
											<div class="col-md-12">
												<div class="mb-3">
													<label class="form-label">Status</label>
													<select class="select">
														<option>Select</option>
														<option>High</option>
														<option>Medium</option>
														<option>Low</option>
													</select>
												</div>
											</div>
										</div>
									</div>
									<div class="p-3 d-flex align-items-center justify-content-end">
										<a href="#" class="btn btn-light me-3">Reset</a>
										<button type="submit" class="btn btn-primary">Apply</button>
									</div>
								</form>
							</div>
						</div>
						<div class="d-flex align-items-center bg-white border rounded-2 p-1 mb-3 me-2">
							<a href="{{url('tickets')}}" class="active btn btn-icon btn-sm me-1 primary-hover"><i class="ti ti-list-tree"></i></a>
							<a href="{{url('ticket-grid')}}" class="btn btn-icon btn-sm bg-light primary-hover"><i class="ti ti-grid-dots"></i></a>
						</div>
						<div class="dropdown mb-3">
							<a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle" data-bs-toggle="dropdown"><i class="ti ti-sort-ascending-2 me-2"></i>Sort by A-Z
							</a>
							<ul class="dropdown-menu p-3">
								<li>
									<a href="javascript:void(0);" class="dropdown-item rounded-1">
										Ascending
									</a>
								</li>
								<li>
									<a href="javascript:void(0);" class="dropdown-item rounded-1">
										Descending
									</a>
								</li>
								<li>
									<a href="javascript:void(0);" class="dropdown-item rounded-1">
										Recently Viewed
									</a>
								</li>
								<li>
									<a href="javascript:void(0);" class="dropdown-item rounded-1">
										Recently Added
									</a>
								</li>
							</ul>
						</div>
					</div>
				</div>

				<div class="row">
					<!-- Tickets -->
					<div class="col-xl-8 col-xxl-9">
						<!-- Ticket List -->
						<div class="card">
							<div class="card-header d-flex align-items-center justify-content-between flex-wrap p-3 pb-0">
								<h5 class="text-primary mb-3">IT Support</h5>
								<div class="d-flex align-items-center mb-1">
									<span class="badge badge-danger d-inline-flex align-items-center mb-2 me-4"><i class="ti ti-circle-filled fs-5 me-1"></i>High</span>
									<div class="mb-2">
										<select class="select">
											<option>Mark as Public</option>
											<option selected>Mark as Private</option>
										</select>
									</div>
								</div>
							</div>
							<div class="card-body p-3 pb-0">
								<div class="d-flex align-items-center justify-content-between flex-wrap">
									<div class="d-flex align-items-center flex-wrap">
										<span class="avatar avatar-xxl me-2 mb-3">
											<img src="{{URL::asset('build/img/students/student-11.jpg')}}" alt="img">
										</span>
										<div class="mb-3">
											<span class="badge bg-pending rounded-pill mb-1">#TK0003</span>
											<div class="d-flex align-items-center mb-2">
												<h5 class="fw-semibold me-2"><a href="{{url('ticket-details')}}">Laptop Issue</a></h5>
												<span class="badge bg-outline-danger d-flex align-items-center ms-1"><i class="ti ti-circle-filled fs-5 me-1"></i>Open</span>
											</div>
											<div class="d-flex align-items-center flex-wrap">
												<p class="text-gray d-flex align-items-center me-2 mb-1">
													<img src="{{URL::asset('build/img/teachers/teacher-02.jpg')}}" class="avatar avatar-xs rounded-circle me-2" alt="img">
													Assigned to <span class="text-dark ms-1"> Daniel</span>
												</p>
												<p class="text-gray d-flex align-items-center mb-1 me-2"><i class="ti ti-calendar-bolt me-1"></i>Updated 22 hours ago</p>
												<p class="text-gray d-flex align-items-center mb-1"><i class="ti ti-message-share me-1"></i>9 Comments</p>
											</div>
										</div>
									</div>
									<div class="mb-3">
										<a href="#" class="btn btn-outline-danger d-flex align-items-center lh-1"><i class="ti ti-trash-x me-1"></i>Delete </a>
									</div>
								</div>
							</div>
						</div>
						<!-- /Ticket List -->

						<!-- Ticket List -->
						<div class="card">
							<div class="card-header d-flex align-items-center justify-content-between flex-wrap p-3 pb-0">
								<h5 class="text-primary mb-3">Maintenance</h5>
								<div class="d-flex align-items-center mb-1">
									<span class="badge badge-success d-inline-flex align-items-center mb-2 me-4"><i class="ti ti-circle-filled fs-5 me-1"></i>Low</span>
									<div class="mb-2">
										<select class="select">
											<option selected>Mark as Public</option>
											<option>Mark as Private</option>
										</select>
									</div>
								</div>
							</div>
							<div class="card-body p-3 pb-0">
								<div class="d-flex align-items-center justify-content-between flex-wrap">
									<div class="d-flex align-items-center flex-wrap">
										<span class="avatar avatar-xxl me-2 mb-3">
											<img src="{{URL::asset('build/img/students/student-01.jpg')}}" alt="img">
										</span>
										<div class="mb-3">
											<span class="badge bg-pending rounded-pill mb-1">#TK0004</span>
											<div class="d-flex align-items-center mb-2">
												<h5 class="fw-semibold me-2"><a href="{{url('ticket-details')}}">Air Conditioning Problem in Classroom</a></h5>
												<span class="badge bg-outline-warning d-flex align-items-center ms-1"><i class="ti ti-circle-filled fs-5 me-1"></i>InProgress</span>
											</div>
											<div class="d-flex align-items-center flex-wrap">
												<p class="text-gray d-flex align-items-center me-2 mb-1">
													<img src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}" class="avatar avatar-xs rounded-circle me-2" alt="img">
													Assigned to <span class="text-dark ms-1"> Teresa</span>
												</p>
												<p class="text-gray d-flex align-items-center mb-1 me-2"><i class="ti ti-calendar-bolt me-1"></i>Updated 20 hours ago</p>
												<p class="text-gray d-flex align-items-center mb-1"><i class="ti ti-message-share me-1"></i>15 Comments</p>
											</div>
										</div>
									</div>
									<div class="mb-3">
										<a href="#" class="btn btn-outline-danger d-flex align-items-center lh-1"><i class="ti ti-trash-x me-1"></i>Delete </a>
									</div>
								</div>
							</div>
						</div>
						<!-- /Ticket List -->

						<!-- Ticket List -->
						<div class="card">
							<div class="card-header d-flex align-items-center justify-content-between flex-wrap p-3 pb-0">
								<h5 class="text-primary mb-3">Transportation Issue</h5>
								<div class="d-flex align-items-center mb-1">
									<span class="badge badge-warning d-inline-flex align-items-center mb-2 me-4"><i class="ti ti-circle-filled fs-5 me-1"></i>Medium
									</span>
									<div class="mb-2">
										<select class="select">
											<option>Mark as Public</option>
											<option selected>Mark as Private</option>
										</select>
									</div>
								</div>
							</div>
							<div class="card-body p-3 pb-0">
								<div class="d-flex align-items-center justify-content-between flex-wrap">
									<div class="d-flex align-items-center flex-wrap">
										<span class="avatar avatar-xxl me-2 mb-3">
											<img src="{{URL::asset('build/img/students/student-05.jpg')}}" alt="img">
										</span>
										<div class="mb-3">
											<span class="badge bg-pending rounded-pill mb-1">#TK0003</span>
											<div class="d-flex align-items-center mb-2">
												<h5 class="fw-semibold me-2"><a href="{{url('ticket-details')}}">School Bus Route Issue</a></h5>
												<span class="badge bg-outline-success d-flex align-items-center ms-1"><i class="ti ti-circle-filled fs-5 me-1"></i>Closed</span>
											</div>
											<div class="d-flex align-items-center flex-wrap">
												<p class="text-gray d-flex align-items-center me-2 mb-1">
													<img src="{{URL::asset('build/img/teachers/teacher-06.jpg')}}" class="avatar avatar-xs rounded-circle me-2" alt="img">
													Assigned to <span class="text-dark ms-1"> James</span>
												</p>
												<p class="text-gray d-flex align-items-center mb-1 me-2"><i class="ti ti-calendar-bolt me-1"></i>Updated 15 hours ago</p>
												<p class="text-gray d-flex align-items-center mb-1"><i class="ti ti-message-share me-1"></i>12 Comments</p>
											</div>
										</div>
									</div>
									<div class="mb-3">
										<a href="#" class="btn btn-outline-danger d-flex align-items-center lh-1"><i class="ti ti-trash-x me-1"></i>Delete </a>
									</div>
								</div>
							</div>
						</div>
						<!-- /Ticket List -->

						<!-- Ticket List -->
						<div class="card">
							<div class="card-header d-flex align-items-center justify-content-between flex-wrap p-3 pb-0">
								<h5 class="text-primary mb-3">Library</h5>
								<div class="d-flex align-items-center mb-1">
									<span class="badge badge-warning d-inline-flex align-items-center mb-2 me-4"><i class="ti ti-circle-filled fs-5 me-1"></i>Medium
									</span>
									<div class="mb-2">
										<select class="select">
											<option>Mark as Public</option>
											<option selected>Mark as Private</option>
										</select>
									</div>
								</div>
							</div>
							<div class="card-body p-3 pb-0">
								<div class="d-flex align-items-center justify-content-between flex-wrap">
									<div class="d-flex align-items-center flex-wrap">
										<span class="avatar avatar-xxl me-2 mb-3">
											<img src="{{URL::asset('build/img/students/student-09.jpg')}}" alt="img">
										</span>
										<div class="mb-3">
											<span class="badge bg-pending rounded-pill mb-1">#TK0002</span>
											<div class="d-flex align-items-center mb-2">
												<h5 class="fw-semibold me-2"><a href="{{url('ticket-details')}}">Library Book Request</a></h5>
												<span class="badge bg-outline-info d-flex align-items-center ms-1"><i class="ti ti-circle-filled fs-5 me-1"></i>Reopened</span>
											</div>
											<div class="d-flex align-items-center flex-wrap">
												<p class="text-gray d-flex align-items-center me-2 mb-1">
													<img src="{{URL::asset('build/img/teachers/teacher-09.jpg')}}" class="avatar avatar-xs rounded-circle me-2" alt="img">
													Assigned to <span class="text-dark ms-1"> Reopened</span>
												</p>
												<p class="text-gray d-flex align-items-center mb-1 me-2"><i class="ti ti-calendar-bolt me-1"></i>Updated 05 hours ago</p>
												<p class="text-gray d-flex align-items-center mb-1"><i class="ti ti-message-share me-1"></i>13 Comments</p>
											</div>
										</div>
									</div>
									<div class="mb-3">
										<a href="#" class="btn btn-outline-danger d-flex align-items-center lh-1"><i class="ti ti-trash-x me-1"></i>Delete </a>
									</div>
								</div>
							</div>
						</div>
						<!-- /Ticket List -->

						<div class="text-center mb-xl-0 mb-4">
							<a href="#" class="btn btn-primary"><i class="ti ti-loader-3 me-2"></i>Load More</a>
						</div>

					</div>
					<!-- /Tickets -->

					<!-- Categories List -->
					<div class="col-xl-4 col-xxl-3 theiaStickySidebar">
						<div class="card">
							<div class="card-header p-3">
								<h4>Ticket Categories</h4>
							</div>
							<div class="card-body p-0">
								<div class="d-flex flex-column">
									<div class="d-flex align-items-center justify-content-between border-bottom p-3">
										<a href="javascript:void(0);">Internet Issue</a> 
										<div class="d-flex align-items-center">
											<span class="badge badge-soft-danger me-2">2</span>
											<span class="badge bg-primary-transparent">0</span>
										</div>
									</div>
									<div class="d-flex align-items-center justify-content-between border-bottom p-3">
										<a href="javascript:void(0);">Computer</a> 
										<div class="d-flex align-items-center">
											<span class="badge badge-soft-danger me-2">2</span>
											<span class="badge bg-primary-transparent">1</span>
										</div>
									</div>
									<div class="d-flex align-items-center justify-content-between border-bottom p-3">
										<a href="javascript:void(0);">Redistribute</a> 
										<div class="d-flex align-items-center">
											<span class="badge bg-primary-transparent">1</span>
										</div>
									</div>
									<div class="d-flex align-items-center justify-content-between border-bottom p-3">
										<a href="javascript:void(0);">Payment</a> 
										<div class="d-flex align-items-center">
											<span class="badge bg-primary-transparent">2</span>
										</div>
									</div>
									<div class="d-flex align-items-center justify-content-between p-3">
										<a href="javascript:void(0);">Complaint</a> 
										<div class="d-flex align-items-center">
											<span class="badge badge-soft-danger me-2">3</span>
											<span class="badge bg-primary-transparent">1</span>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="card mb-0">
							<div class="card-header p-3">
								<h4>Support Agents</h4>
							</div>
							<div class="card-body p-0">
								<div class="d-flex flex-column">
									<div class="d-flex align-items-center justify-content-between border-bottom p-3">
										<span class="d-flex align-items-center">
											<img src="{{URL::asset('build/img/teachers/teacher-03.jpg')}}" class="avatar avatar-xs rounded-circle me-2" alt="img">Hellana
										</span>
										<div class="d-flex align-items-center">
											<span class="badge badge-soft-danger me-2">2</span>
											<span class="badge bg-primary-transparent">0</span>
										</div>
									</div>
									<div class="d-flex align-items-center justify-content-between border-bottom p-3">
										<span class="d-flex align-items-center">
											<img src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}" class="avatar avatar-xs rounded-circle me-2" alt="img">Teresa
										</span>
										<div class="d-flex align-items-center">
											<span class="badge badge-soft-danger me-2">2</span>
											<span class="badge bg-primary-transparent">1</span>
										</div>
									</div>
									<div class="d-flex align-items-center justify-content-between border-bottom p-3">
										<span class="d-flex align-items-center">
											<img src="{{URL::asset('build/img/teachers/teacher-02.jpg')}}" class="avatar avatar-xs rounded-circle me-2" alt="img">Daniel
										</span>
										<div class="d-flex align-items-center">
											<span class="badge bg-primary-transparent">1</span>
										</div>
									</div>
									<div class="d-flex align-items-center justify-content-between p-3">
										<span class="d-flex align-items-center">
											<img src="{{URL::asset('build/img/teachers/teacher-07.jpg')}}" class="avatar avatar-xs rounded-circle me-2" alt="img">Willie
										</span>
										<div class="d-flex align-items-center">
											<span class="badge bg-primary-transparent">2</span>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<!-- /Categories List -->

				</div>

			</div>
		</div>
		<!-- /Page Wrapper -->            

    @component('components.modal-popup')        
    @endcomponent
@endsection
