<?php $page = 'class-time-table'; ?>
@extends('layout.mainlayout')
@section('content')
   <!-- Page Wrapper -->
		<div class="page-wrapper">
			<div class="content">
		@component('components.breadcrumb')
		@slot('title')
		Time Table
		@endslot
		@slot('item1')
		Dashboard
		@endslot
		@slot('item2')
		Academic
        @endslot
		@slot('item3')
		Time Table
		@endslot
	@endcomponent
				<div class="card">
					<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
						<h4 class="mb-3">Time Table</h4>
						<div class="d-flex align-items-center flex-wrap">
							<div class="dropdown mb-3 me-2">
								<a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle"
									data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"><i
										class="ti ti-filter me-2"></i>Filter</a>
								<div class="dropdown-menu drop-width">
									<form action="{{url('class-time-table')}}">
										<div class="d-flex align-items-center border-bottom p-3">
											<h4>Filter</h4>
										</div>
										<div class="p-3 pb-0 border-bottom">
											<div class="row">
												<div class="col-md-12">
													<div class="mb-3">
														<label class="form-label">Class</label>
														<select class="select">
															<option>Select</option>
															<option>I</option>
															<option>II</option>
															<option>III</option>
														</select>
													</div>
												</div>
												<div class="col-md-12">
													<div class="mb-3">
														<label class="form-label">Section</label>
														<select class="select">
															<option>Select</option>
															<option>A</option>
															<option>B</option>
															<option>C</option>
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
						</div>
					</div>
					<div class="card-body pb-0">
						<div class="d-flex flex-nowrap overflow-auto">
							<div class="d-flex flex-column me-4 flex-fill">
								<div class="mb-3">
									<h6>Monday</h6>
								</div>
								<div class="bg-transparent-danger rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>09:00 - 09:45 AM</p>
									<p class="text-dark">Subject : Maths</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-07.jpg')}}" alt="Img"></span>
											Jacquelin
										</a>
									</div>
								</div>
								<div class="bg-transparent-primary rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>09:45 - 10:30 AM</p>
									<p class="text-dark">Subject : English</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-03.jpg')}}" alt="Img"></span>
											Hellana
										</a>
									</div>
								</div>
								<div class="bg-transparent-success rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>10:45 - 11:30 AM</p>
									<p class="text-dark">Subject : Computer</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-02.jpg')}}" alt="Img"></span>
											Daniel
										</a>
									</div>
								</div>
								<div class="bg-transparent-pending rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>11:30 - 12:15 PM</p>
									<p class="text-dark">Subject : Spanish</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-03.jpg')}}" alt="Img"></span>
											Erickson
										</a>
									</div>
								</div>
								<div class="bg-transparent-info rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>01:30 - 02:15 PM</p>
									<p class="text-dark">Subject : Science</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-05.jpg')}}" alt="Img"></span>
											Morgan
										</a>
									</div>
								</div>
								<div class="bg-transparent-light rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>02:15 - 03:00 PM</p>
									<p class="text-dark">Subject : Chemistry</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-06.jpg')}}" alt="Img"></span>
											Aaron
										</a>
									</div>
								</div>
								<div class="bg-transparent-warning rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>03:15 - 04:00 PM</p>
									<p class="text-dark">Subject : Physics</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}" alt="Img"></span>
											Teresa
										</a>
									</div>
								</div>
							</div>
							<div class="d-flex flex-column me-4 flex-fill">
								<div class="mb-3">
									<h6>Tuesday</h6>
								</div>
								<div class="bg-transparent-pending rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>09:00 - 09:45 AM</p>
									<p class="text-dark">Subject : Spanish</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-03.jpg')}}" alt="Img"></span>
											Erickson
										</a>
									</div>
								</div>
								<div class="bg-transparent-warning rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>09:45 - 10:30 AM</p>
									<p class="text-dark">Subject : Physics</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}" alt="Img"></span>
											Teresa
										</a>
									</div>
								</div>
								<div class="bg-transparent-light rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>10:45 - 11:30 AM</p>
									<p class="text-dark">Subject : Chemistry</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-06.jpg')}}" alt="Img"></span>
											Aaron
										</a>
									</div>
								</div>
								<div class="bg-transparent-danger rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>11:30 - 12:15 PM</p>
									<p class="text-dark">Subject : Maths</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-07.jpg')}}" alt="Img"></span>
											Jacquelin
										</a>
									</div>
								</div>
								<div class="bg-transparent-success rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>01:30 - 02:15 PM</p>
									<p class="text-dark">Subject : Computer</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-02.jpg')}}" alt="Img"></span>
											Daniel
										</a>
									</div>
								</div>
								<div class="bg-transparent-primary rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>02:15 - 03:00 PM</p>
									<p class="text-dark">Subject : English</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-03.jpg')}}" alt="Img"></span>
											Hellana
										</a>
									</div>
								</div>
								<div class="bg-transparent-info rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>03:15 - 04:00 PM</p>
									<p class="text-dark">Subject : Science</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-05.jpg')}}" alt="Img"></span>
											Morgan
										</a>
									</div>
								</div>
							</div>
							<div class="d-flex flex-column me-4 flex-fill">
								<div class="mb-3">
									<h6>Wednesday</h6>
								</div>
								<div class="bg-transparent-success rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>09:00 - 09:45 AM</p>
									<p class="text-dark">Subject : Computer</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-02.jpg')}}" alt="Img"></span>
											Daniel
										</a>
									</div>
								</div>

								<div class="bg-transparent-info rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>09:45 - 10:30 AM</p>
									<p class="text-dark">Subject : Science</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-05.jpg')}}" alt="Img"></span>
											Morgan
										</a>
									</div>
								</div>
								<div class="bg-transparent-danger rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>10:45 - 11:30 AM</p>
									<p class="text-dark">Subject : Maths</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-07.jpg')}}" alt="Img"></span>
											Jacquelin
										</a>
									</div>
								</div>
								<div class="bg-transparent-light rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>11:30 - 12:15 PM</p>
									<p class="text-dark">Subject : Chemistry</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-06.jpg')}}" alt="Img"></span>
											Aaron
										</a>
									</div>
								</div>
								<div class="bg-transparent-warning rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>01:30 - 02:15 PM</p>
									<p class="text-dark">Subject : Physics</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}" alt="Img"></span>
											Teresa
										</a>
									</div>
								</div>
								<div class="bg-transparent-primary rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>02:15 - 03:00 PM</p>
									<p class="text-dark">Subject : English</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-03.jpg')}}" alt="Img"></span>
											Hellana
										</a>
									</div>
								</div>
								<div class="bg-transparent-pending rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>03:15 - 04:00 PM</p>
									<p class="text-dark">Subject : Spanish</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-03.jpg')}}" alt="Img"></span>
											Erickson
										</a>
									</div>
								</div>
							</div>
							<div class="d-flex flex-column me-4 flex-fill">
								<div class="mb-3">
									<h6>Thursday</h6>
								</div>
								<div class="bg-transparent-warning rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>09:00 - 09:45 AM</p>
									<p class="text-dark">Subject : Physics</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}" alt="Img"></span>
											Teresa
										</a>
									</div>
								</div>
								<div class="bg-transparent-success rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>09:45 - 10:30 AM</p>
									<p class="text-dark">Subject : Computer</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-02.jpg')}}" alt="Img"></span>
											Daniel
										</a>
									</div>
								</div>
								<div class="bg-transparent-primary rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>10:45 - 11:30 AM</p>
									<p class="text-dark">Subject : English</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-03.jpg')}}" alt="Img"></span>
											Hellana
										</a>
									</div>
								</div>
								<div class="bg-transparent-info rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>11:30 - 12:15 PM</p>
									<p class="text-dark">Subject : Science</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-05.jpg')}}" alt="Img"></span>
											Morgan
										</a>
									</div>
								</div>
								<div class="bg-transparent-pending rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>01:30 - 02:15 PM</p>
									<p class="text-dark">Subject : Spanish</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-03.jpg')}}" alt="Img"></span>
											Erickson
										</a>
									</div>
								</div>
								<div class="bg-transparent-light rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>02:15 - 03:00 PM</p>
									<p class="text-dark">Subject : Chemistry</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-06.jpg')}}" alt="Img"></span>
											Aaron
										</a>
									</div>
								</div>
								<div class="bg-transparent-danger rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>03:15 - 04:00 PM</p>
									<p class="text-dark">Subject : Maths</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-07.jpg')}}" alt="Img"></span>
											Jacquelin
										</a>
									</div>
								</div>
							</div>
							<div class="d-flex flex-column me-4 flex-fill">
								<div class="mb-3">
									<h6>Friday</h6>
								</div>
								<div class="bg-transparent-primary rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>09:00 - 09:45 AM</p>
									<p class="text-dark">Subject : English</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-03.jpg')}}" alt="Img"></span>
											Hellana
										</a>
									</div>
								</div>
								<div class="bg-transparent-pending rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>09:45 - 10:30 AM</p>
									<p class="text-dark">Subject : Spanish</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-03.jpg')}}" alt="Img"></span>
											Erickson
										</a>
									</div>
								</div>
								<div class="bg-transparent-warning rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>10:45 - 11:30 AM</p>
									<p class="text-dark">Subject : Physics</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}" alt="Img"></span>
											Teresa
										</a>
									</div>
								</div>
								<div class="bg-transparent-light rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>11:30 - 12:15 PM</p>
									<p class="text-dark">Subject : Chemistry</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-06.jpg')}}" alt="Img"></span>
											Aaron
										</a>
									</div>
								</div>
								<div class="bg-transparent-danger rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>01:30 - 02:15 PM</p>
									<p class="text-dark">Subject : Maths</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-07.jpg')}}" alt="Img"></span>
											Jacquelin
										</a>
									</div>
								</div>
								<div class="bg-transparent-success rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>02:15 - 3:00 PM</p>
									<p class="text-dark">Subject : Computer</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-02.jpg')}}" alt="Img"></span>
											Daniel
										</a>
									</div>
								</div>
								<div class="bg-transparent-info rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>03:15 - 04:00 PM</p>
									<p class="text-dark">Subject : Science</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-05.jpg')}}" alt="Img"></span>
											Morgan
										</a>
									</div>
								</div>
							</div>
							<div class="d-flex flex-column me-4 flex-fill">
								<div class="mb-3">
									<h6>Saturday</h6>
								</div>
								<div class="bg-transparent-primary rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>09:00 - 09:45 AM</p>
									<p class="text-dark">Subject : English</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-03.jpg')}}" alt="Img"></span>
											Hellana
										</a>
									</div>
								</div>
								<div class="bg-transparent-pending rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>09:45 - 10:30 AM</p>
									<p class="text-dark">Subject : Spanish</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-03.jpg')}}" alt="Img"></span>
											Erickson
										</a>
									</div>
								</div>
								<div class="bg-transparent-warning rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>10:45 - 11:30 AM</p>
									<p class="text-dark">Subject : Physics</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}" alt="Img"></span>
											Teresa
										</a>
									</div>
								</div>
								<div class="bg-transparent-light rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>11:30 - 12:15 PM</p>
									<p class="text-dark">Subject : Chemistry</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-06.jpg')}}" alt="Img"></span>
											Aaron
										</a>
									</div>
								</div>
								<div class="bg-transparent-danger rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>01:30 - 02:15 PM</p>
									<p class="text-dark">Subject : Maths</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-07.jpg')}}" alt="Img"></span>
											Jacquelin
										</a>
									</div>
								</div>
								<div class="bg-transparent-success rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>02:15 - 3:00 PM</p>
									<p class="text-dark">Subject : Computer</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-02.jpg')}}" alt="Img"></span>
											Daniel
										</a>
									</div>
								</div>
								<div class="bg-transparent-info rounded p-3 mb-4">
									<p class="d-flex align-items-center text-gray text-nowrap mb-1"><i
											class="ti ti-clock me-1"></i>03:15 - 04:00 PM</p>
									<p class="text-dark">Subject : Science</p>
									<div class="bg-white rounded p-1 mt-3">
										<a href="{{url('teacher-details')}}" class="text-muted d-flex align-items-center">
											<span class="avatar avatar-sm me-2"><img
													src="{{URL::asset('build/img/teachers/teacher-05.jpg')}}" alt="Img"></span>
											Morgan
										</a>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="card-footer border-0 pb-0">
						<div class="row">
							<div class="col-lg-4 col-xxl-4 col-xl-4 d-flex">
								<div class="card flex-fill">
									<div class="card-body">
										<span class="bg-primary badge badge-sm mb-2">Morning Break</span>
										<p class="text-dark"><i class="ti ti-clock me-1"></i>10:30 to 10 :45 AM</p>
									</div>
								</div>
							</div>
							<div class="col-lg-4 col-xxl-3 d-flex">
								<div class="card flex-fill">
									<div class="card-body">
										<span class="bg-warning badge badge-sm mb-2">Lunch</span>
										<p class="text-dark"><i class="ti ti-clock me-1"></i>10:30 to 10 :45 AM</p>
									</div>
								</div>
							</div>
							<div class="col-lg-4 col-xxl-3 d-flex">
								<div class="card flex-fill">
									<div class="card-body">
										<span class="bg-info badge badge-sm mb-2">Evening Break</span>
										<p class="text-dark"><i class="ti ti-clock me-1"></i>03:30 PM to 03:45 PM</p>
									</div>
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
