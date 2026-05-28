import { usePage } from '@inertiajs/vue3';

export function useCheckRole() {
    const $page = usePage();

    const hasRole = (roles) => {
        if (!Array.isArray(roles)) {
            roles = [roles]
        }
        return roles.includes($page.props.defs?.current_venue_role)
    }

    const isOwner = () => hasRole('owner')
    const isGeneralManager = () => hasRole('general_manager')
    const isSectionManager = () => hasRole('section_manager')
    const isAttendant = () => hasRole('attendant')

    const isManager = () => hasRole(['owner', 'general_manager', 'section_manager'])
    const isStaff = () => hasRole(['owner', 'general_manager', 'section_manager', 'attendant'])

    return { hasRole, isOwner, isGeneralManager, isSectionManager, isAttendant, isManager, isStaff }
}