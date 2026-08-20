<?php $page = 'exam-results'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
		<div class="page-wrapper">
			<div class="content">
				@component('components.breadcrumb')
				@slot('title')
				Exam Result
				@endslot
				@slot('item1')
				Dashboard
				@endslot
				@slot('item2')
				Academic

				@endslot
				@slot('item3')
				Exam Result
				@endslot
			   @endcomponent
				

				<!-- Guardians List -->
				<div class="card">
					<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
						<h4 class="mb-3">Exam Results</h4>
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
									<form action="{{url('exam-results')}}">
										<div class="d-flex align-items-center border-bottom p-3">
											<h4>Filter</h4>
										</div>
										<div class="p-3 border-bottom pb-0">
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
												<div class="col-md-12">
													<div class="mb-3">
														<label class="form-label">Exam Type</label>
														<select class="select">
															<option>Select</option>
															<option>Weekly Test</option>
															<option>Monthly Test</option>
															<option>Chapter Wise Test</option>
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

						<!-- Guardians List -->
						<div class="custom-datatable-filter table-responsive">
							<table class="table datatable">
								<thead class="thead-light">
									<tr>
										<th class="no-sort">
											<div class="form-check form-check-md">
												<input class="form-check-input" type="checkbox" id="select-all">
											</div>
										</th>
										<th>Admission No</th>
										<th>Student Name</th>
										<th>English</th>
										<th>Spanish</th>
										<th>Physics </th>
										<th>Chemistry</th>
										<th>Maths</th>
										<th>Computer</th>
										<th>Env Science</th>
										<th>Total</th>
										<th>Percent(%)</th>
										<th>Grade</th>
										<th>Result</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td>
											<div class="form-check form-check-md">
												<input class="form-check-input" type="checkbox">
											</div>
										</td>
										<td><a href="#" class="link-primary">AD9892434</a></td>
										<td>
											<div class="d-flex align-items-center">
											  <a href="{{url('student-details')}}" class="avatar avatar-md"><img
												  src="{{URL::asset('build/img/students/student-01.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
											  <div class="ms-2">
												<p class="mb-0" class="text-dark"><a href="{{url('student-details')}}">Janet</a></p>
												<span class="fs-12">Roll No : 35013</span>
											  </div>
											</div>
										</td>
										<td><span class="attendance-range">95</span></td>
										<td><span class="attendance-range">93</span></td>
										<td><span class="attendance-range">88</span></td>
										<td><span class="attendance-range">90</span></td>
										<td><span class="attendance-range">85</span></td>
										<td><span class="attendance-range">98</span></td>
										<td><span class="attendance-range">95</span></td>
										<td><span class="attendance-range">644</span></td>
										<td><span class="attendance-range">92</span></td>
										<td><span class="attendance-range">O</span></td>
										<td>
											<span class="badge badge-soft-success"><i
													class="ti ti-filled"></i>Pass</span>
										</td>
									</tr>
									<tr>
										<td>
											<div class="form-check form-check-md">
												<input class="form-check-input" type="checkbox">
											</div>
										</td>
										<td><a href="#" class="link-primary">AD9892433</a></td>
										<td>
											<div class="d-flex align-items-center">
											  <a href="{{url('student-details')}}" class="avatar avatar-md"><img
												  src="{{URL::asset('build/img/students/student-02.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
											  <div class="ms-2">
												<p class="mb-0" class="text-dark"><a href="{{url('student-details')}}">Joann</a></p>
												<span class="fs-12">Roll No : 35012</span>
											  </div>
											</div>
										</td>
										<td><span class="text-danger">30</span></td>
										<td><span>35</span></td>
										<td><span >36</span></td>
										<td><span class="text-danger">28</span></td>
										<td><span>38</span></td>
										<td><span>40</span></td>
										<td><span>37</span></td>
										<td><span>244</span></td>
										<td><span>34</span></td>
										<td><span>F</span></td>
										<td>
											<span class="badge badge-soft-danger"><i
													class="ti ti-filled"></i>Fail</span>
										</td>
									</tr>
									<tr>
										<td>
											<div class="form-check form-check-md">
												<input class="form-check-input" type="checkbox">
											</div>
										</td>
										<td><a href="#" class="link-primary">AD9892432</a></td>
										<td>
											<div class="d-flex align-items-center">
											  <a href="{{url('student-details')}}" class="avatar avatar-md"><img
												  src="{{URL::asset('build/img/students/student-03.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
											  <div class="ms-2">
												<p class="mb-0" class="text-dark"><a href="{{url('student-details')}}">Kathleen</a></p>
												<span class="fs-12">Roll No : 35011</span>
											  </div>
											</div>
										</td>
										<td><span >70</span></td>
										<td><span >80</span></td>
										<td><span >85</span></td>
										<td><span >78</span></td>
										<td><span >82</span></td>
										<td><span >83</span></td>
										<td><span >80</span></td>
										<td><span >558</span></td>
										<td><span >79</span></td>
										<td><span >A</span></td>
										<td>
											<span class="badge badge-soft-success"><i
													class="ti ti-filled"></i>Pass</span>
										</td>
									</tr>
									<tr>
										<td>
											<div class="form-check form-check-md">
												<input class="form-check-input" type="checkbox">
											</div>
										</td>
										<td><a href="#" class="link-primary">AD9892431</a></td>
										<td>
											<div class="d-flex align-items-center">
											  <a href="{{url('student-details')}}" class="avatar avatar-md"><img
												  src="{{URL::asset('build/img/students/student-04.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
											  <div class="ms-2">
												<p class="mb-0" class="text-dark"><a href="{{url('student-details')}}">Gifford</a></p>
												<span class="fs-12">Roll No : 35010</span>
											  </div>
											</div>
										</td>
										<td><span >60</span></td>
										<td><span >68</span></td>
										<td><span >65</span></td>
										<td><span >70</span></td>
										<td><span >67</span></td>
										<td><span >72</span></td>
										<td><span >75</span></td>
										<td><span >477</span></td>
										<td><span >68</span></td>
										<td><span >B+</span></td>
										<td>
											<span class="badge badge-soft-success"><i
													class="ti ti-filled"></i>Pass</span>
										</td>
									</tr>
									<tr>
										<td>
											<div class="form-check form-check-md">
												<input class="form-check-input" type="checkbox">
											</div>
										</td>
										<td><a href="#" class="link-primary">AD9892430</a></td>
										<td>
											<div class="d-flex align-items-center">
											  <a href="{{url('student-details')}}" class="avatar avatar-md"><img
												  src="{{URL::asset('build/img/students/student-05.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
											  <div class="ms-2">
												<p class="mb-0" class="text-dark"><a href="{{url('student-details')}}">Lisa</a></p>
												<span class="fs-12">Roll No : 35009</span>
											  </div>
											</div>
										</td>
										<td><span>36</span></td>
										<td><span class="text-danger">30</span></td>
										<td><span >40</span></td>
										<td><span >38</span></td>
										<td><span >50</span></td>
										<td><span >48</span></td>
										<td><span >43</span></td>
										<td><span >285</span></td>
										<td><span >40</span></td>
										<td><span >F</span></td>
										<td>
											<span class="badge badge-soft-danger"><i
													class="ti ti-filled"></i>Fail</span>
										</td>
									</tr>
									<tr>
										<td>
											<div class="form-check form-check-md">
												<input class="form-check-input" type="checkbox">
											</div>
										</td>
										<td><a href="#" class="link-primary">AD9892429</a></td>
										<td>
											<div class="d-flex align-items-center">
											  <a href="{{url('student-details')}}" class="avatar avatar-md"><img
												  src="{{URL::asset('build/img/students/student-06.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
											  <div class="ms-2">
												<p class="mb-0" class="text-dark"><a href="{{url('student-details')}}">Ralph</a></p>
												<span class="fs-12">Roll No : 35008</span>
											  </div>
											</div>
										</td>
										<td><span>43</span></td>
										<td><span>50</span></td>
										<td><span>62</span></td>
										<td><span>70</span></td>
										<td><span >65</span></td>
										<td><span >58</span></td>
										<td><span >60</span></td>
										<td><span >408</span></td>
										<td><span >58</span></td>
										<td><span >B</span></td>
										<td>
											<span class="badge badge-soft-success"><i
													class="ti ti-filled"></i>Pass</span>
										</td>
									</tr>
									<tr>
										<td>
											<div class="form-check form-check-md">
												<input class="form-check-input" type="checkbox">
											</div>
										</td>
										<td><a href="#" class="link-primary">AD9892428</a></td>
										<td>
											<div class="d-flex align-items-center">
											  <a href="{{url('student-details')}}" class="avatar avatar-md"><img
												  src="{{URL::asset('build/img/students/student-07.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
											  <div class="ms-2">
												<p class="mb-0" class="text-dark"><a href="{{url('student-details')}}">Julie</a></p>
												<span class="fs-12">Roll No : 35007</span>
											  </div>
											</div>
										</td>
										<td><span>72</span></td>
										<td><span>80</span></td>
										<td><span>75</span></td>
										<td><span>78</span></td>
										<td><span>84</span></td>
										<td><span>87</span></td>
										<td><span>76</span></td>
										<td><span>552</span></td>
										<td><span>78</span></td>
										<td><span>A</span></td>
										<td>
											<span class="badge badge-soft-success"><i
													class="ti ti-filled"></i>Pass</span>
										</td>
									</tr>
									<tr>
										<td>
											<div class="form-check form-check-md">
												<input class="form-check-input" type="checkbox">
											</div>
										</td>
										<td><a href="#" class="link-primary">AD9892427</a></td>
										<td>
											<div class="d-flex align-items-center">
											  <a href="{{url('student-details')}}" class="avatar avatar-md"><img
												  src="{{URL::asset('build/img/students/student-08.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
											  <div class="ms-2">
												<p class="mb-0" class="text-dark"><a href="{{url('student-details')}}">Ryan</a></p>
												<span class="fs-12">Roll No : 35006</span>
											  </div>
											</div>
										</td>
										<td><span>40</span></td>
										<td><span >48</span></td>
										<td><span >42</span></td>
										<td><span>47</span></td>
										<td><span class="text-danger">32</span></td>
										<td><span>58</span></td>
										<td><span>50</span></td>
										<td><span>317</span></td>
										<td><span>45</span></td>
										<td><span>F</span></td>
										<td>
											<span class="badge badge-soft-danger"><i
													class="ti ti-filled"></i>Fail</span>
										</td>
									</tr>
									<tr>
										<td>
											<div class="form-check form-check-md">
												<input class="form-check-input" type="checkbox">
											</div>
										</td>
										<td><a href="#" class="link-primary">AD9892426</a></td>
										<td>
											<div class="d-flex align-items-center">
											  <a href="{{url('student-details')}}" class="avatar avatar-md"><img
												  src="{{URL::asset('build/img/students/student-09.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
											  <div class="ms-2">
												<p class="mb-0" class="text-dark"><a href="{{url('student-details')}}">Susan</a></p>
												<span class="fs-12">Roll No : 35004</span>
											  </div>
											</div>
										</td>
										<td><span>60</span></td>
										<td><span>62</span></td>
										<td><span>65</span></td>
										<td><span>70</span></td>
										<td><span>72</span></td>
										<td><span>63</span></td>
										<td><span>78</span></td>
										<td><span>470</span></td>
										<td><span>67</span></td>
										<td><span>B+</span></td>
										<td>
											<span class="badge badge-soft-success"><i
													class="ti ti-filled"></i>Pass</span>
										</td>
									</tr>
									<tr>
										<td>
											<div class="form-check form-check-md">
												<input class="form-check-input" type="checkbox">
											</div>
										</td>
										<td><a href="#" class="link-primary">AD9892425</a></td>
										<td>
											<div class="d-flex align-items-center">
											  <a href="{{url('student-details')}}" class="avatar avatar-md"><img
												  src="{{URL::asset('build/img/students/student-09.jpg')}}" class="img-fluid rounded-circle" alt="img"></a>
											  <div class="ms-2">
												<p class="mb-0" class="text-dark"><a href="{{url('student-details')}}">Susan</a></p>
												<span class="fs-12">Roll No : 35004</span>
											  </div>
											</div>
										</td>
										<td><span>72</span></td>
										<td><span>78</span></td>
										<td><span>85</span></td>
										<td><span>83</span></td>
										<td><span>86</span></td>
										<td><span>90</span></td>
										<td><span>92</span></td>
										<td><span>586</span></td>
										<td><span>83</span></td>
										<td><span>A+</span></td>
										<td>
											<span class="badge badge-soft-success"><i
													class="ti ti-filled"></i>Pass</span>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<!-- /Guardians List -->
					</div>
				</div>
				<!-- /Guardians List -->

			</div>
		</div>
		<!-- /Page Wrapper -->
@endsection
