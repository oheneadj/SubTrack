<div>
    <x-ui.page-header :title="$user->name" subtitle="View and manage user details">
        <div class="flex items-center gap-2">
            <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline flex items-center gap-2">
                <x-icon-arrow-left class="w-4 h-4" />
                <span>Back to Users</span>
            </a>
            <button wire:click="toggleActive" class="btn btn-sm {{ $user->is_active ? 'btn-error' : 'btn-success' }}">
                {{ $user->is_active ? 'Disable Account' : 'Enable Account' }}
            </button>
            <button wire:click="initiatePasswordReset" class="btn btn-sm btn-primary">
                Reset Password
            </button>
        </div>
    </x-ui.page-header>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- User Info Card -->
        <div class="md:col-span-2 space-y-6">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="avatar placeholder">
                            <div class="bg-primary text-primary-content rounded-full w-16">
                                <span class="text-xl">{{ $user->initials() }}</span>
                            </div>
                        </div>
                        <div>
                            <h2 class="card-title text-2xl">{{ $user->name }}</h2>
                            <p class="text-slate-500">{{ $user->email }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-4 bg-slate-50 rounded-lg">
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Role</p>
                            <div class="flex items-center gap-2">
                                <span class="badge badge-lg {{ $user->isSuperAdmin() ? 'badge-primary' : 'badge-neutral' }}">
                                    {{ $user->role->label() ?? $user->role->value }}
                                </span>
                            </div>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-lg">
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Status</p>
                            <x-ui.badge-status :status="$user->is_active ? 'Active' : 'Disabled'" />
                        </div>

                        <div class="p-4 bg-slate-50 rounded-lg">
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Invitation Status</p>
                            @if($user->invitation_accepted_at)
                                <div class="flex items-center text-success gap-1">
                                    <x-icon-check class="w-4 h-4" />
                                    <span class="font-medium">Joined {{ $user->invitation_accepted_at->diffForHumans() }}</span>
                                </div>
                            @else
                                <div class="flex items-center text-warning gap-1">
                                    <x-icon-clock class="w-4 h-4" />
                                    <span class="font-medium">Pending Acceptance</span>
                                </div>
                            @endif
                        </div>

                        <div class="p-4 bg-slate-50 rounded-lg">
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Last Login</p>
                            <p class="font-medium">
                                {{ $user->last_login_at ? $user->last_login_at->format('M d, Y H:i') : 'Never' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity / Recent Actions (Placeholder for now) -->
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                    <h3 class="card-title text-lg mb-4">Account History</h3>
                    <div class="text-center py-8 text-slate-400">
                        <x-icon-list-details class="w-12 h-12 mx-auto mb-2 opacity-20" />
                        <p>No recent activity recorded for this user.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar / Meta -->
        <div class="space-y-6">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                    <h3 class="card-title text-base mb-4">Meta Data</h3>
                    <div class="space-y-4 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-500">ID:</span>
                            <span class="font-mono">{{ $user->id }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Created At:</span>
                            <span>{{ $user->created_at->format('M d, Y') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Email Verified:</span>
                            <span>{{ $user->email_verified_at ? 'Yes' : 'No' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if($user->is_active === false)
                <div class="alert alert-warning shadow-sm">
                    <x-icon-alert-triangle class="w-5 h-5" />
                    <div>
                        <h3 class="font-bold">Account Disabled</h3>
                        <div class="text-xs">This user cannot log in until their account is re-enabled.</div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Password Reset Modal -->
    <x-ui.modal id="reset-password-modal" title="Confirm Password Reset">
        <div class="py-4">
            @if(!$passwordResetDone)
                <p class="mb-4">Are you sure you want to reset the password for <strong>{{ $user->name }}</strong>?</p>
                <p class="text-sm text-slate-500 mb-6">A new random password will be generated and displayed for you to share with the user.</p>
                
                <div class="flex justify-end gap-3">
                    <button @click="$dispatch('close-modal', { id: 'reset-password-modal' })" class="btn btn-ghost btn-sm">Cancel</button>
                    <button wire:click="confirmPasswordReset" class="btn btn-primary btn-sm">Generate New Password</button>
                </div>
            @else
                <div class="p-4 bg-green-50 rounded-lg border border-green-200 mb-6">
                    <div class="flex items-center gap-2 text-green-700 font-bold mb-2">
                        <x-icon-check class="w-5 h-5" />
                        <span>Password Reset Successful!</span>
                    </div>
                    <p class="text-xs text-green-600">Please copy the new password below and share it with the user.</p>
                </div>

                <div class="p-4 bg-slate-50 rounded-lg border border-slate-200">
                    <p class="text-xs font-semibold text-slate-400 uppercase mb-2">Generated New Password:</p>
                    <div class="flex items-center justify-between">
                        <code class="text-lg font-bold text-primary">{{ $newPassword }}</code>
                        <button type="button" class="btn btn-ghost btn-xs" onclick="navigator.clipboard.writeText('{{ $newPassword }}')">Copy</button>
                    </div>
                </div>

                <div class="flex justify-end mt-6">
                    <button @click="$dispatch('close-modal', { id: 'reset-password-modal' })" class="btn btn-primary btn-sm">Done</button>
                </div>
            @endif
        </div>
    </x-ui.modal>
</div>
