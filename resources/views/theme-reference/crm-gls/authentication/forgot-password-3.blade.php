<?php $page = 'forgot-password-3'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="container">
	<div class="row justify-content-center">
		<div class="col-md-5">
			<form action="{{url('reset-password-3')}}">
				<div class="d-flex flex-column justify-content-between vh-100">
					<div class=" mx-auto p-4 text-center">
						<img src="{{URL::asset('build/img/authentication/authentication-logo.svg')}}"
							class="img-fluid" alt="Logo">
					</div>
					<div class="card">
						<div class="card-body p-4">
							<div class=" mb-4">
								<h2 class="mb-2">Forgot Password?</h2>
								<p class="mb-0">If you forgot your password, well, then we’ll email you instructions to reset your password.</p>
							</div>
							<div class="mb-3 ">
								<label class="form-label">Email Address</label>
								<div class="input-icon mb-3 position-relative">
									<span class="input-icon-addon">
										<i class="ti ti-mail"></i>
									</span>
									<input type="text" value="" class="form-control">
								</div>
							</div>
							<div class="mb-3">
								<button type="submit" class="btn btn-primary w-100">Sign In</button>
							</div>
							<div class="text-center">
								<h6 class="fw-normal text-dark mb-0">Return to <a
										href="{{url('login-3')}}" class="hover-a "> Login</a>
								</h6>
							</div>
						</div>
					</div>
					<div class="p-4 text-center">
						<p class="mb-0 ">Copyright &copy; 2024 - Preskool</p>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>

@endsection