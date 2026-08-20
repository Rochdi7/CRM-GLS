<?php $page = 'library-return'; ?>
@extends('layout.mainlayout')
@section('content')
  	<!-- Page Wrapper -->
	  <div class="page-wrapper">
		<div class="content">
			@component('components.breadcrumb')
			@slot('title')
			Return Book
			@endslot
			@slot('item1')
			Dashboard
			@endslot
			@slot('item2')
			Management
			@endslot
			@slot('item3')
			Return Book
			@endslot
		   @endcomponent
			

			<!-- Students List -->
			<div class="card">
				<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
					<h4 class="mb-3">Return Book</h4>
					<div class="d-flex align-items-center flex-wrap">
						<div class="input-icon-start mb-3 me-2 position-relative">
                            <span class="icon-addon">
                                <i class="ti ti-calendar"></i>
                            </span>
							<input type="text" class="form-control  date-range bookingrange" placeholder="Select"
								value="Academic Year : 2024 / 2025">
						</div>
						<div class="dropdown mb-3 me-2">
							<a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle"
								data-bs-toggle="dropdown" data-bs-auto-close="outside"><i
									class="ti ti-filter me-2"></i>Filter</a>
							<div class="dropdown-menu drop-width">
								<form action="{{url('library-return')}}">
									<div class="d-flex align-items-center border-bottom p-3">
										<h4>Filter</h4>
									</div>
									<div class="p-3 border-bottom">
										<div class="row">
											<div class="col-md-6">
												<div class="mb-3">
													<label class="form-label">Date of Issue</label>
													<select class="select">
														<option>Select Date of Issue</option>
														<option>20 Apr 2024</option>
														<option>24 Apr 2024</option>
														<option>02 May 2024</option>
														<option>16 May 2024</option>
														<option>22 May 2024</option>
													</select>
												</div>
											</div>
											<div class="col-md-6">
												<div class="mb-3">
													<label class="form-label">Name</label>
													<select class="select">
														<option>Select Name</option>
														<option>Janet</option>
														<option>Joann</option>
														<option>Kathleen</option>
														<option>Gifford</option>
													</select>
												</div>
											</div>
											<div class="col-md-12">
												<div class="mb-0">
													<label class="form-label">More Filter</label>
													<select class="select">
														<option>Filters</option>
														<option>ID</option>
														<option>Date of Issue</option>
														<option>Due Date</option>
														<option>Issue To</option>
														<option>Books Issued</option>
														<option>Book Returned</option>
														<option>Issue Remarks</option>
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

						<div class="dropdown mb-3">
							<a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle"
								data-bs-toggle="dropdown"><i class="ti ti-sort-ascending-2 me-2"></i>Sort by A-Z
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
				<div class="card-body p-0 py-3">

					<!-- Student List -->
					<div class="custom-datatable-filter table-responsive">
						<table class="table datatable">
							<thead class="thead-light">
								<tr>
									<th class="no-sort">
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox" id="select-all">
										</div>
									</th>
									<th>ID</th>
									<th>Date of Issue</th>
									<th>Due Date</th>
									<th>Issue To</th>
									<th>Books Issued</th>
									<th>Book Returned</th>
									<th>Issue Remarks</th>
									<th></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox">
										</div>
									</td>
									<td><a href="#" class="link-primary">IB853629</a></td>
									<td>20 Apr 2024</td>
									<td>19 May 2024</td>
									<td>
										<div class="d-flex align-items-center">
											<a href="{{url('student-details')}}" class="avatar avatar-md"><img
													src="{{URL::asset('build/img/students/student-01.jpg')}}"
													class="img-fluid rounded-circle" alt="img"></a>
											<div class="ms-2">
												<p class="text-dark mb-0"><a href="{{url('student-details')}}">Janet</a>
												</p>
												<span class="fs-12">III, A</span>
											</div>
										</div>
									</td>
									<td>1</td>
									<td>0
									</td>
									<td>Book Issued</td>
									<td>
										<a href="#" class="btn btn-light add-fee">View Details</a>
									</td>
								</tr>
								<tr>
									<td>
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox">
										</div>
									</td>
									<td><a href="#" class="link-primary">IB853628</a></td>
									<td>24 Apr 2024</td>
									<td>20 May 2024</td>
									<td>
										<div class="d-flex align-items-center">
											<a href="{{url('student-details')}}" class="avatar avatar-md"><img
													src="{{URL::asset('build/img/students/student-02.jpg')}}"
													class="img-fluid rounded-circle" alt="img"></a>
											<div class="ms-2">
												<p class="text-dark mb-0"><a href="{{url('student-details')}}">Joann</a>
												</p>
												<span class="fs-12">IV, B</span>
											</div>
										</div>
									</td>
									<td>5</td>
									<td>3
									</td>
									<td>Book Issued</td>
									<td>
										<a href="#" class="btn btn-light add-fee">View Details</a>
									</td>
								</tr>
								<tr>
									<td>
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox">
										</div>
									</td>
									<td><a href="#" class="link-primary">IB853627</a></td>
									<td>02 May 2024</td>
									<td>01 Jun 2024</td>
									<td>
										<div class="d-flex align-items-center">
											<a href="{{url('student-details')}}" class="avatar avatar-md"><img
													src="{{URL::asset('build/img/students/student-03.jpg')}}"
													class="img-fluid rounded-circle" alt="img"></a>
											<div class="ms-2">
												<p class="text-dark mb-0"><a href="{{url('student-details')}}">Kathleen</a>
												</p>
												<span class="fs-12">III, A</span>
											</div>
										</div>
									</td>
									<td>4</td>
									<td>2
									</td>
									<td>Book Issued</td>
									<td>
										<a href="#" class="btn btn-light add-fee">View Details</a>
									</td>
								</tr>
								<tr>
									<td>
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox">
										</div>
									</td>
									<td><a href="#" class="link-primary">IB853626</a></td>
									<td>16 May 2024</td>
									<td>15 Jun 2024</td>
									<td>
										<div class="d-flex align-items-center">
											<a href="{{url('student-details')}}" class="avatar avatar-md"><img
													src="{{URL::asset('build/img/students/student-04.jpg')}}"
													class="img-fluid rounded-circle" alt="img"></a>
											<div class="ms-2">
												<p class="text-dark mb-0"><a href="{{url('student-details')}}">Gifford</a>
												</p>
												<span class="fs-12">I, B</span>
											</div>
										</div>
									</td>
									<td>3</td>
									<td>2
									</td>
									<td>Book Issued</td>
									<td>
										<a href="#" class="btn btn-light add-fee">View Details</a>
									</td>
								</tr>
								<tr>
									<td>
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox">
										</div>
									</td>
									<td><a href="#" class="link-primary">IB853625</a></td>
									<td>22 May 2024</td>
									<td>20 Jun 2024</td>
									<td>
										<div class="d-flex align-items-center">
											<a href="{{url('student-details')}}" class="avatar avatar-md"><img
													src="{{URL::asset('build/img/students/student-05.jpg')}}"
													class="img-fluid rounded-circle" alt="img"></a>
											<div class="ms-2">
												<p class="text-dark mb-0"><a href="{{url('student-details')}}">Lisa</a>
												</p>
												<span class="fs-12">II, B</span>
											</div>
										</div>
									</td>
									<td>6</td>
									<td>4
									</td>
									<td>Book Issued</td>
									<td>
										<a href="#" class="btn btn-light add-fee">View Details</a>
									</td>
								</tr>
								<tr>
									<td>
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox">
										</div>
									</td>
									<td><a href="#" class="link-primary">IB853624</a></td>
									<td>10 Jun 2024</td>
									<td>08 Jul 2024</td>
									<td>
										<div class="d-flex align-items-center">
											<a href="{{url('student-details')}}" class="avatar avatar-md"><img
													src="{{URL::asset('build/img/students/student-06.jpg')}}"
													class="img-fluid rounded-circle" alt="img"></a>
											<div class="ms-2">
												<p class="text-dark mb-0"><a href="{{url('student-details')}}">Ralph</a>
												</p>
												<span class="fs-12">III, B</span>
											</div>
										</div>
									</td>
									<td>4</td>
									<td>2
									</td>
									<td>Book Issued</td>
									<td>
										<a href="#" class="btn btn-light add-fee">View Details</a>
									</td>
								</tr>
								<tr>
									<td>
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox">
										</div>
									</td>
									<td><a href="#" class="link-primary">IB853623</a></td>
									<td>15 Jun 2024</td>
									<td>14 Jul 2024</td>
									<td>
										<div class="d-flex align-items-center">
											<a href="{{url('student-details')}}" class="avatar avatar-md"><img
													src="{{URL::asset('build/img/students/student-07.jpg')}}"
													class="img-fluid rounded-circle" alt="img"></a>
											<div class="ms-2">
												<p class="text-dark mb-0"><a href="{{url('student-details')}}">Julie</a>
												</p>
												<span class="fs-12">V, A</span>
											</div>
										</div>
									</td>
									<td>5</td>
									<td>3
									</td>
									<td>Book Issued</td>
									<td>
										<a href="#" class="btn btn-light add-fee">View Details</a>
									</td>
								</tr>
								<tr>
									<td>
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox">
										</div>
									</td>
									<td><a href="#" class="link-primary">IB853622</a></td>
									<td>26 Jun 2024</td>
									<td>25 Jul 2024</td>
									<td>
										<div class="d-flex align-items-center">
											<a href="{{url('student-details')}}" class="avatar avatar-md"><img
													src="{{URL::asset('build/img/students/student-08.jpg')}}"
													class="img-fluid rounded-circle" alt="img"></a>
											<div class="ms-2">
												<p class="text-dark mb-0"><a href="{{url('student-details')}}">Ryan</a>
												</p>
												<span class="fs-12">VI, A</span>
											</div>
										</div>
									</td>
									<td>3</td>
									<td>1
									</td>
									<td>Book Issued</td>
									<td>
										<a href="#" class="btn btn-light add-fee">View Details</a>
									</td>
								</tr>
								<tr>
									<td>
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox">
										</div>
									</td>
									<td><a href="#" class="link-primary">IB853621</a></td>
									<td>06 Jul 2024</td>
									<td>05 Aug 2024</td>
									<td>
										<div class="d-flex align-items-center">
											<a href="{{url('student-details')}}" class="avatar avatar-md"><img
													src="{{URL::asset('build/img/students/student-09.jpg')}}"
													class="img-fluid rounded-circle" alt="img"></a>
											<div class="ms-2">
												<p class="text-dark mb-0"><a href="{{url('student-details')}}">Susan</a>
												</p>
												<span class="fs-12">VIII, B</span>
											</div>
										</div>
									</td>
									<td>6</td>
									<td>4
									</td>
									<td>Book Issued</td>
									<td>
										<a href="#" class="btn btn-light add-fee">View Details</a>
									</td>
								</tr>
								<tr>
									<td>
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox">
										</div>
									</td>
									<td><a href="#" class="link-primary">IB853620</a></td>
									<td>18 Jul 2024</td>
									<td>16 Aug 2024</td>
									<td>
										<div class="d-flex align-items-center">
											<a href="{{url('student-details')}}" class="avatar avatar-md"><img
													src="{{URL::asset('build/img/students/student-12.jpg')}}"
													class="img-fluid rounded-circle" alt="img"></a>
											<div class="ms-2">
												<p class="text-dark mb-0"><a href="{{url('student-details')}}">Richard</a>
												</p>
												<span class="fs-12">VII, B</span>
											</div>
										</div>
									</td>
									<td>2</td>
									<td>1
									</td>
									<td>Book Issued</td>
									<td>
										<a href="#" class="btn btn-light add-fee">View Details</a>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
					<!-- /Student List -->
				</div>
			</div>
			<!-- /Students List -->

		</div>
	</div>
	<!-- /Page Wrapper -->

@endsection
