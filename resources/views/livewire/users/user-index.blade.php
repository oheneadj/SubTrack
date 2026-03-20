<div>
    <x-ui.page-header title="User Management" subtitle="Invite, manage, and control access for your team">
        <button wire:click="openInvite" class="btn btn-primary btn-sm flex items-center gap-2">
            <x-icon-plus class="w-4 h-4" />
            <span>Invite User</span>
        </button>
    </x-ui.page-header>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success mb-6 rounded-xl border-green-200">
            <x-icon-circle-check class="w-5 h-5" />
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error mb-6 rounded-xl border-red-200">
            <x-icon-alert-triangle class="w-5 h-5" />
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Search --}}
    <div class="mb-6">
        <x-ui.form-input model="search" placeholder="Search by name or email..." />
    </div>

    {{-- Users Table --}}
    @if($this->users->isEmpty())
        <x-ui.empty-state
            icon="users"
            title="No users found"
            message="Invite your first team member to get started."
        />
    @else
        <div wire:loading.class="opacity-50 pointer-events-none">
            <x-ui.data-table :headers="['User', 'Email', 'Role', 'Active', 'Joined', 'Actions']">
                @foreach($this->users as $user)
                    <tr class="hover:bg-slate-50 transition-colors {{ !$user->is_active ? 'opacity-60' : '' }}">
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="avatar placeholder">
                                    <div class="w-9 h-9 rounded-full {{ $user->is_active ? 'bg-blue-100 text-blue-600' : 'bg-slate-200 text-slate-400' }} flex items-center justify-center font-bold text-xs uppercase">
                                        {{ $user->initials() }}
                                    </div>
                                </div>
                                <div>
                                    <a href="{{ route('users.show', $user) }}" class="font-semibold text-primary text-sm hover:underline decoration-blue-300 underline-offset-4">{{ $user->name }}</a>
                                    @if($user->id === auth()->id())
                                        <span class="text-[10px] text-blue-500 font-medium">(You)</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="text-sm text-secondary">{{ $user->email }}</td>
                        <td>
                            @if($user->role === \App\Enums\UserRole::SuperAdmin)
                                <span class="badge badge-sm bg-blue-100 text-blue-700 border-blue-200 font-semibold">Super Admin</span>
                            @else
                                <span class="badge badge-sm bg-slate-100 text-slate-600 border-slate-200 font-semibold">User</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full {{ $user->is_active ? 'bg-green-500' : 'bg-red-500' }}"></div>
                                <span class="text-xs font-semibold {{ $user->is_active ? 'text-green-600' : 'text-red-500' }}">
                                    {{ $user->is_active ? 'Active' : 'Disabled' }}
                                </span>
                            </div>
                        </td>
                        <td>
                            @if($user->invitation_accepted_at)
                                <div class="text-sm text-secondary flex items-center gap-1">
                                    <x-icon-check class="w-3.5 h-3.5 text-success" />
                                    <span>Joined</span>
                                </div>
                            @else
                                <div class="text-sm text-amber-600 flex items-center gap-1">
                                    <x-icon-clock class="w-3.5 h-3.5" />
                                    <span>Pending</span>
                                </div>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('users.show', $user) }}" class="btn btn-ghost btn-xs text-blue-600 hover:bg-blue-50">
                                    Details
                                </a>
                                @if($user->id !== auth()->id())
                                    <x-ui.action-menu deleteAction="confirmDelete({{ $user->id }})">
                                        <button wire:click="resendInvite({{ $user->id }})" class="btn btn-ghost btn-xs text-slate-600 flex items-center gap-2 justify-start">
                                            <x-icon-refresh class="w-3 h-3" />
                                            <span>Resend Invite</span>
                                        </button>
                                        <button wire:click="openToggleModal({{ $user->id }})" class="btn btn-ghost btn-xs {{ $user->is_active ? 'text-error' : 'text-success' }} flex items-center gap-2 justify-start">
                                            <x-icon-alert-triangle class="w-3 h-3" />
                                            <span>{{ $user->is_active ? 'Disable' : 'Enable' }}</span>
                                        </button>
                                    </x-ui.action-menu>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-ui.data-table>
        </div>

        <div class="mt-4">
            {{ $this->users->links() }}
        </div>
    @endif

    {{-- Invite Modal --}}
    @if($showInviteModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" x-data @keydown.escape.window="$wire.set('showInviteModal', false)">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xl w-full max-w-md p-6 mx-4" @click.away="$wire.set('showInviteModal', false)">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-primary">Invite New User</h3>
                <button wire:click="$set('showInviteModal', false)" class="btn btn-ghost btn-square btn-sm">
                    <x-icon-x class="w-4 h-4" />
                </button>
            </div>

            <form wire:submit="sendInvite" class="space-y-4">
                <x-ui.form-input label="Full Name" model="inviteName" placeholder="e.g. Jane Smith" :error="$errors->first('inviteName')" />
                <x-ui.form-input label="Email Address" model="inviteEmail" type="email" placeholder="jane@example.com" :error="$errors->first('inviteEmail')" />

                <x-ui.form-select model="inviteRole" label="Role">
                    <option value="user">User</option>
                    <option value="super_admin">Super Admin</option>
                </x-ui.form-select>

                <p class="text-xs text-secondary bg-blue-50 border border-blue-100 rounded-lg p-3">
                    📧 A random password will be generated and emailed to the user along with login instructions.
                </p>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="$set('showInviteModal', false)" class="btn btn-ghost btn-sm">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" class="btn btn-primary btn-sm">
                        <span wire:loading.remove wire:target="sendInvite">Send Invite</span>
                        <span wire:loading wire:target="sendInvite"><span class="loading loading-spinner loading-xs"></span> Sending...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Toggle Confirm Modal (Password Required) --}}
    @if($showToggleModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" x-data @keydown.escape.window="$wire.set('showToggleModal', false)">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xl w-full max-w-sm p-6 mx-4" @click.away="$wire.set('showToggleModal', false)">
            <div class="text-center mb-5">
                @if($toggleUserIsActive)
                    <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-amber-100 flex items-center justify-center">
                        <x-icon-alert-triangle class="w-6 h-6 text-amber-600" />
                    </div>
                    <h3 class="text-lg font-bold text-primary">Disable User</h3>
                    <p class="text-sm text-secondary mt-1">This user will be logged out and unable to sign in.</p>
                @else
                    <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-green-100 flex items-center justify-center">
                        <x-icon-circle-check class="w-6 h-6 text-green-600" />
                    </div>
                    <h3 class="text-lg font-bold text-primary">Enable User</h3>
                    <p class="text-sm text-secondary mt-1">This user will be able to sign in again.</p>
                @endif
            </div>

            <form wire:submit="confirmToggleActive" class="space-y-4">
                <x-ui.form-input label="Enter your password to confirm" model="confirmPassword" type="password" placeholder="Your password" :error="$errors->first('confirmPassword')" />

                <div class="flex gap-3 pt-1">
                    <button type="button" wire:click="$set('showToggleModal', false)" class="btn btn-ghost btn-sm flex-1">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" class="btn btn-sm flex-1 {{ $toggleUserIsActive ? 'btn-warning' : 'btn-success' }}">
                        <span wire:loading.remove wire:target="confirmToggleActive">{{ $toggleUserIsActive ? 'Disable' : 'Enable' }}</span>
                        <span wire:loading wire:target="confirmToggleActive"><span class="loading loading-spinner loading-xs"></span></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
