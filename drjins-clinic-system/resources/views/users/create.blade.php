@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div class="container">
            <h1 class="fw-bold">Register User</h1>
            <p class="mb-0">Create a clinic account for an administrator, doctor, or staff member.</p>
        </div>
    </div>
    <div class="container">
            @if (session('success'))<div class="alert alert-success alert-dismissible fade show" role="status">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
            <div class="card clinic-card mb-4"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-center"><div><h2 class="h5 mb-1">Registered accounts</h2><p class="small text-muted mb-0">Click an account to view its details.</p></div><a href="#registration-form" class="btn btn-primary">Register User</a></div><div class="list-group mt-3">@forelse($users as $account)<button type="button" class="list-group-item list-group-item-action account-detail-button d-flex justify-content-between" data-user-id="{{ $account->id }}" data-user-name="{{ $account->name }}"><span>{{ $account->name }}<small class="d-block text-muted">{{ $account->email }}</small></span><span class="badge text-bg-light align-self-center">{{ ucfirst($account->role) }}</span></button>@empty<p class="text-muted mb-0">No accounts registered.</p>@endforelse</div></div></div>
        <div id="registration-backdrop" class="registration-backdrop" @if(!$errors->any() && !old('name')) hidden @endif>
        <div id="registration-form" class="card clinic-card shadow-sm border mx-auto" role="dialog" aria-modal="true" aria-labelledby="registration-title" style="max-width: 680px">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4"><h2 id="registration-title" class="h5 mb-0">Register New User</h2><button type="button" class="btn-close" id="close-registration-form" aria-label="Close"></button></div>
                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form method="POST" action="{{ route('users.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">Full name</label>
                        <input id="name" name="name" class="form-control" value="{{ old('name') }}" maxlength="255" autocomplete="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input id="email" name="email" type="email" class="form-control" value="{{ old('email') }}" maxlength="255" autocomplete="off" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select id="role" name="role" class="form-select" required>
                            <option value="staff" @selected(old('role', 'staff') === 'staff')>Staff</option>
                            <option value="doctor" @selected(old('role') === 'doctor')>Doctor</option>
                            <option value="admin" @selected(old('role') === 'admin')>Administrator</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input id="password" name="password" type="password" class="form-control" minlength="8" autocomplete="new-password" required>
                        <div class="form-text">Use at least 8 characters.</div>
                    </div>
                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">Confirm password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" minlength="8" autocomplete="new-password" required>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-clinic">Register User</button>
                    </div>
                </form>
            </div>
        </div>
        </div>
    </div>
    <div class="modal fade" id="accountDetailsModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h2 class="h5 mb-0">Account details</h2><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div id="account-detail-error" class="alert alert-danger d-none"></div><form id="account-detail-form"><label class="form-label" for="admin_password">Admin password</label><input id="admin_password" name="admin_password" type="password" class="form-control" required><button class="btn btn-primary mt-3">Verify and view</button></form><div id="account-detail-result" class="mt-3 d-none"></div></div></div></div></div>
    <style>.registration-backdrop{position:fixed;z-index:1055;inset:0;display:flex;align-items:center;justify-content:center;padding:1rem;background:rgba(18,30,38,.58);overflow-y:auto}.registration-backdrop[hidden]{display:none!important}#registration-form{position:relative;width:min(680px,100%);max-height:calc(100vh - 2rem);overflow-y:auto;background:#fff;flex:none}</style>
    <script>(()=>{const backdrop=document.getElementById('registration-backdrop'),close=document.getElementById('close-registration-form');document.querySelectorAll('a[href="#registration-form"]').forEach(button=>button.addEventListener('click',event=>{event.preventDefault();backdrop.hidden=false;document.body.style.overflow='hidden';document.getElementById('name')?.focus();}));const hide=()=>{backdrop.hidden=true;document.body.style.overflow='';};close?.addEventListener('click',hide);backdrop?.addEventListener('click',event=>{if(event.target===backdrop)hide();});document.addEventListener('keydown',event=>{if(event.key==='Escape'&&!backdrop.hidden)hide();});})();</script>
    <script>
    (()=>{const modal=document.getElementById('accountDetailsModal'),form=document.getElementById('account-detail-form'),result=document.getElementById('account-detail-result'),error=document.getElementById('account-detail-error');let id;const csrf=document.querySelector('meta[name=csrf-token]').content;document.querySelectorAll('.account-detail-button').forEach(b=>b.addEventListener('click',()=>{id=b.dataset.userId;form.classList.remove('d-none');result.classList.add('d-none');error.classList.add('d-none');form.reset();bootstrap.Modal.getOrCreateInstance(modal).show();}));form.addEventListener('submit',async e=>{e.preventDefault();const response=await fetch(`/users/${id}/details`,{method:'POST',headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json','Content-Type':'application/json'},body:JSON.stringify({admin_password:form.admin_password.value})});const data=await response.json();if(!response.ok){error.textContent=data.message||'Admin password verification failed.';error.classList.remove('d-none');return;}form.classList.add('d-none');const p=document.createElement('p');p.className='small text-muted';p.textContent=data.password?'Password: '+data.password:'Password was set before password viewing was enabled and cannot be recovered.';result.innerHTML=`<p><strong>Name:</strong> ${data.name}</p><p><strong>Email:</strong> ${data.email}</p><p><strong>Role:</strong> ${data.role}</p>`;result.appendChild(p);result.insertAdjacentHTML('beforeend',`<hr><form method="POST" action="/users/${id}" data-confirm-message="Delete this user account?" data-confirm-danger="true">@csrf @method('DELETE')<input type="hidden" name="admin_password" value="${form.admin_password.value}"><button class="btn btn-outline-danger">Delete user</button></form>`);result.classList.remove('d-none');});})();
    </script>
@endsection
