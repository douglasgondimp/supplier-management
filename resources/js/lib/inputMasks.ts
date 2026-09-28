export type InputMask = 'cpf' | 'cnpj' | 'phone' | 'cep';

export function formatInput(
    value: string | null | undefined,
    mask: InputMask,
): string {
    const digits = (value ?? '').replace(/\D/g, '');
    const patterns: Record<InputMask, string> = {
        cpf: '###.###.###-##',
        cnpj: '##.###.###/####-##',
        phone: digits.length > 10 ? '(##) #####-####' : '(##) ####-####',
        cep: '#####-###',
    };
    let result = '';
    let index = 0;
    for (const character of patterns[mask]) {
        if (index >= digits.length) break;
        result += character === '#' ? digits[index++] : character;
    }
    return result;
}
